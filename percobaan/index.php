<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Fungsi untuk mendapatkan jumlah total dari tabel
function getTotal($table) {
    global $config;
    $result = $config->query("SELECT COUNT(*) as total FROM $table");
    return $result->fetch_assoc()['total'];
}

// Fungsi untuk mendapatkan penjualan harian
function getDailySales() {
    global $config;
    $result = $config->query("SELECT SUM(total) as total FROM penjualan WHERE DATE(tanggal_input) = CURDATE()");
    return $result->fetch_assoc()['total'] ?? 0;
}

// Fungsi untuk mendapatkan jumlah barang yang telah terjual
function getTotalSoldItems() {
    global $config;
    $result = $config->query("SELECT SUM(jumlah) as total FROM detail_penjualan");
    return $result->fetch_assoc()['total'] ?? 0;
}

// Fungsi untuk mendapatkan data kategori barang
function getCategoryData() {
    global $config;
    $query = "SELECT 
                kategori.nama_kategori, 
                SUM(barang.stok) AS total_stok
            FROM 
                kategori
            JOIN 
                barang ON kategori.id_kategori = barang.id_kategori
            GROUP BY 
                kategori.id_kategori, kategori.nama_kategori";
    $result = $config->query($query);

    $data = ['labels' => [], 'values' => []];
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['nama_kategori'];  // Menyimpan nama kategori
        $data['values'][] = $row['total_stok'];    // Menyimpan total stok
    }

    return $data;
}

// Fungsi untuk mendapatkan data barang terlaris
function getTopSellingItems() {
    global $config;

    // Query untuk mendapatkan barang terlaris berdasarkan jumlah terjual
    $query = "SELECT 
                barang.nama_barang, 
                SUM(detail_penjualan.jumlah) AS total_terjual
              FROM 
                detail_penjualan
              LEFT JOIN 
                barang ON detail_penjualan.kodebarang = barang.kodebarang  -- Menggunakan kode_barang untuk menghubungkan
              GROUP BY 
                barang.nama_barang
              ORDER BY 
                total_terjual DESC
              LIMIT 10";

    // Eksekusi query
    $result = $config->query($query);

    // Menyiapkan array untuk menyimpan hasil
    $data = ['labels' => [], 'values' => []];
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['nama_barang'];
        $data['values'][] = $row['total_terjual'];
    }

    // Kembalikan data dalam bentuk array
    return $data;
}

// Query untuk mendapatkan penjualan berdasarkan periode
function getSalesByPeriod($periodType, $periodValue) {
    global $config;
    
    switch($periodType) {
        case 'tahun':
            $query = "SELECT 
                        YEAR(tanggal_input) AS tahun,
                        SUM(total) AS total_penjualan
                      FROM 
                        penjualan
                      WHERE 
                        YEAR(tanggal_input) = $periodValue
                      GROUP BY 
                        YEAR(tanggal_input)
                      ORDER BY 
                        tahun DESC";
            break;
        
        case 'bulan':
            $query = "SELECT 
                        MONTH(tanggal_input) AS bulan,
                        SUM(total) AS total_penjualan
                      FROM 
                        penjualan
                      WHERE 
                        YEAR(tanggal_input) = YEAR(CURDATE())  -- Ganti dengan tahun yang diinginkan
                        AND MONTH(tanggal_input) = $periodValue
                      GROUP BY 
                        YEAR(tanggal_input), MONTH(tanggal_input)
                      ORDER BY 
                        bulan DESC";
            break;
        
        case 'minggu':
            $query = "SELECT 
                        WEEK(tanggal_input, 1) AS minggu,
                        SUM(total) AS total_penjualan
                      FROM 
                        penjualan
                      WHERE 
                        YEAR(tanggal_input) = YEAR(CURDATE())  -- Ganti dengan tahun yang diinginkan
                        AND WEEK(tanggal_input, 1) = $periodValue
                      GROUP BY 
                        YEAR(tanggal_input), WEEK(tanggal_input, 1)
                      ORDER BY 
                        minggu DESC";
            break;
        
        default:
            return "Invalid period type";
    }

    $result = $config->query($query);
    
    // Menyimpan hasil query dalam array
    $data = ['labels' => [], 'values' => []];
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['tahun'] ?? $row['bulan'] ?? $row['minggu'];
        $data['values'][] = $row['total_penjualan'];
    }

    return $data;
}


// Data kategori untuk Pie Chart
$categoryData = getCategoryData();

// Data barang terlaris untuk Bar Chart
$topSellingItems = getTopSellingItems();

// Data untuk Bar Chart Penjualan berdasarkan filter
$filter = $_GET['filter'] ?? 'bulan';
$data = ['labels' => [], 'values' => []];

switch ($filter) {
    case 'minggu':
        $query = "SELECT WEEK(tanggal_input) as minggu, COUNT(*) as jumlah_penjualan
                  FROM penjualan
                  WHERE YEAR(tanggal_input) = YEAR(CURDATE())
                  GROUP BY minggu";
        break;
    case 'tahun':
        $query = "SELECT YEAR(tanggal_input) as tahun, COUNT(*) as jumlah_penjualan
                  FROM penjualan
                  GROUP BY tahun";
        break;
    default: // 'bulan'
        $query = "SELECT MONTH(tanggal_input) as bulan, COUNT(*) as jumlah_penjualan
                  FROM penjualan
                  WHERE YEAR(tanggal_input) = YEAR(CURDATE())
                  GROUP BY bulan";
}

$result = $config->query($query);

while ($row = $result->fetch_assoc()) {
    $data['labels'][] = $filter === 'bulan' 
        ? date('F', mktime(0, 0, 0, $row['bulan'], 10)) 
        : ($filter === 'minggu' ? "Minggu {$row['minggu']}" : $row['tahun']);
    $data['values'][] = $row['jumlah_penjualan'];
}

echo json_encode($data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f3f4f6;
    min-height: 100vh;
}

/* Navbar Styling */
.navbar {
    background-color: #800000;
    color: #ffffff;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.navbar-brand {
    font-size: 1.8rem;
    font-weight: bold;
    color: #f8f9fa;
}

.navbar-user {
    display: flex;
    align-items: center;
    gap: 15px;
}

.navbar-user span {
    color: #ffffff;
}

.navbar-user a {
    color: #ffffff;
    text-decoration: none;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s ease;
}

.navbar-user a:hover {
    color: #f0f0f0;
}

/* Sidebar Styling */
.sidebar {
    background-color: #343a40;
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 60px;
    left: 0;
    padding-top: 20px;
    z-index: 99;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.sidebar-menu {
    list-style: none;
    padding-left: 0;
}

.sidebar-menu li {
    position: relative;
}

.sidebar-menu li a {
    color: #ffffff;
    text-decoration: none;
    padding: 15px 20px;
    display: block;
    transition: all 0.3s ease-in-out;
    border-bottom: 1px solid #484848;
    font-size: 1rem;
}

.sidebar-menu li a:hover {
    background-color: #0062cc;
    padding-left: 25px;
}

.sidebar-menu li a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

/* Submenu Styling */
.submenu {
    display: none;
    list-style: none;
    background-color: #2c3136;
}

.submenu li a {
    padding: 12px 20px 12px 50px;
    font-size: 0.9rem;
    border-bottom: 1px solid #404040;
}

.sidebar-menu li.active > .submenu {
    display: block;
}

/* Main Content Area */
.main-content {
    margin-left: 250px;
    margin-top: 60px;
    padding: 30px;
    min-height: calc(100vh - 60px);
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 1;
}

.main-content h1 {
    margin-top: 0;
    padding-top: 20px;
    margin-bottom: 30px;
    color: #333;
    font-size: 2rem;
    font-weight: 600;
}

/* Dashboard Cards */
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* Mengubah menjadi 4 kolom */
    gap: 15px; /* Menyesuaikan jarak antar kotak */
    margin-bottom: 40px;
}

.card {
    background-color: #ffffff;
    padding: 20px; /* Menyesuaikan padding agar lebih kecil jika diperlukan */
    border-radius: 10px; /* Menyesuaikan sudut kotak */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
    cursor: pointer;
    border: 1px solid #eaeaea;
    position: relative;
    overflow: hidden;
}

.card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    transform: translateY(-5px);
    background-color: #f8f9fa;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background-color: #007bff;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.card:hover::before {
    opacity: 1;
}

.card-title {
    font-size: 1.2rem;
    color: #6c757d;
    margin-bottom: 15px;
    font-weight: 500;
}

.card-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: #007bff;
    line-height: 1.2;
}

/* Chart Container */
.chart-container {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-top: 30px;
    height: 400px; /* Tambahkan tinggi tetap */
}

/* Perbaikan Grafik */
.row {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.col-md-12, .col-md-6 {
    flex: 1;
}

.chart-container canvas {
    width: 100% !important;
    height: 100% !important;
}

.filter-container {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    margin-bottom: 20px;
    gap: 10px;
}

.filter-container label {
    font-weight: bold;
    margin-right: 10px;
}

#timeFilter {
    padding: 5px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 60px;
    }

    .sidebar-menu li a span {
        display: none;
    }

    .sidebar-menu li a i {
        margin-right: 0;
    }

    .main-content {
        margin-left: 60px;
        width: calc(100% - 60px);
    }

    .dashboard-cards {
        grid-template-columns: 1fr;
    }

    .navbar {
        padding: 1rem;
    }

    .navbar-brand {
        font-size: 1.5rem;
    }

    .card {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .navbar {
        flex-direction: column;
        padding: 0.5rem;
    }

    .navbar-user {
        margin-top: 10px;
    }

    .main-content {
        padding: 15px;
    }

    .card-value {
        font-size: 2rem;
    }
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">Toko Baju</div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($_SESSION['user']['email']); ?></span>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Dashboard</h1>


        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Total Barang</div>
                <div class="card-value"><?php echo getTotal('barang'); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Supplier</div>
                <div class="card-value"><?php echo getTotal('supplier'); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Penjualan Hari Ini</div>
                <div class="card-value">Rp <?php echo number_format(getDailySales(), 0, ',', '.'); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Barang Terjual</div>
                <div class="card-value"><?php echo getTotalSoldItems(); ?></div>
            </div>
        </div>

        <!-- Filter Waktu -->
        <div class="filter-container">
            <label for="timeFilter">Filter Waktu:</label>
            <select id="timeFilter" onchange="updateCharts()">
                <option value="bulan">Bulanan</option>
                <option value="minggu">Mingguan</option>
                <option value="tahun">Tahunan</option>
            </select>
        </div>

        <!-- Graphs Container -->
        <div class="row">
            <!-- Full Width Line Chart -->
            <div class="col-md-12">
                <div class="chart-container">
                    <h3>Penjualan Berdasarkan Periode</h3>
                    <canvas id="periodChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Pie Chart and Bar Chart Container -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3>Kategori Barang</h3>
                    <canvas id="categoryChart" class="pie-chart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3>Top 10 Barang Terlaris</h3>
                    <canvas id="topSellingItemsChart" class="bar-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data Penjualan berdasarkan Periode
        var periodData = <?php echo json_encode($data); ?>;

        // Data Kategori Barang (Pie Chart)
        var categoryData = <?php echo json_encode($categoryData); ?>;

        // Data Barang Terlaris (Bar Chart)
        var topSellingItems = <?php echo json_encode($topSellingItems); ?>;

        // Variabel global untuk menyimpan instance chart
let periodChart, categoryChart, topSellingItemsChart;

// Fungsi untuk menghancurkan chart sebelum membuat ulang
function destroyCharts() {
    if (periodChart) periodChart.destroy();
    if (categoryChart) categoryChart.destroy();
    if (topSellingItemsChart) topSellingItemsChart.destroy();
}

// Fungsi untuk membuat ulang grafik dengan data baru
function initializeCharts(periodData, categoryData, topSellingItems) {
    // Hancurkan chart yang ada sebelumnya
    destroyCharts();

    // Chart Periode Penjualan (Line Chart)
    const periodCtx = document.getElementById('periodChart').getContext('2d');
    periodChart = new Chart(periodCtx, {
        type: 'line',
        data: {
            labels: periodData.labels,
            datasets: [{
                label: 'Penjualan',
                data: periodData.values,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(75, 192, 192, 0.2)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Chart Kategori Barang (Pie Chart)
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categoryData.labels,
            datasets: [{
                data: categoryData.values,
                backgroundColor: [
                    '#ff6347', '#f0ad4e', '#5bc0de', 
                    '#5cb85c', '#d9534f'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Chart Barang Terlaris (Bar Chart)
    const topSellingCtx = document.getElementById('topSellingItemsChart').getContext('2d');
    topSellingItemsChart = new Chart(topSellingCtx, {
        type: 'bar',
        data: {
            labels: topSellingItems.labels,
            datasets: [{
                label: 'Barang Terlaris',
                data: topSellingItems.values,
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Fungsi untuk memperbarui grafik berdasarkan filter
function updateCharts() {
    const timeFilter = document.getElementById('timeFilter').value;
    
    fetch('get_chart_data.php?filter=' + timeFilter)
        .then(response => response.json())
        .then(data => {
            // Perbarui grafik dengan data baru
            periodChart.data.labels = data.periodData.labels;
            periodChart.data.datasets[0].data = data.periodData.values;
            periodChart.update();
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Inisialisasi awal
document.addEventListener('DOMContentLoaded', () => {
    initializeCharts(
        periodData, 
        categoryData, 
        topSellingItems
    );

    // Tambahkan event listener untuk filter
    document.getElementById('timeFilter').addEventListener('change', updateCharts);
});
        </script>
</body>
</html>