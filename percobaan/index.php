<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

function validateDateRange($start_date, $end_date) {
    $start = DateTime::createFromFormat('Y-m-d', $start_date);
    $end = DateTime::createFromFormat('Y-m-d', $end_date);
    return $start && $end && $start <= $end;
}

function getDateRange() {
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    $start_date = date('Y-m-d', strtotime($start_date));
    $end_date = date('Y-m-d', strtotime($end_date));

    if (!validateDateRange($start_date, $end_date)) {
        $start_date = date('Y-m-d', strtotime('-1 month'));
        $end_date = date('Y-m-d');
    }

    return ['start' => $start_date, 'end' => $end_date];
}

function getTotal($table) {
    global $config;
    $query = $config->prepare("SELECT COUNT(*) as total FROM $table");
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getTotalStockByCategory() {
    global $config;
    $query = $config->prepare("SELECT 
        k.nama_kategori, 
        SUM(b.stok) as total_stok
    FROM kategori k
    LEFT JOIN barang b ON k.id_kategori = b.id_kategori
    GROUP BY k.id_kategori, k.nama_kategori
    ORDER BY total_stok DESC");
    
    $query->execute();
    $result = $query->get_result();
    
    $data = ['labels' => [], 'values' => []];
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['nama_kategori'];
        $data['values'][] = $row['total_stok'];
    }
    
    return $data;
}

function getTotalPenjualan($dateRange) {
    global $config;
    $query = $config->prepare("SELECT COALESCE(SUM(total), 0) as total_penjualan 
        FROM penjualan 
        WHERE DATE(tanggal_input) = CURDATE()");
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    return $row['total_penjualan'];
}

function getTotalBarangTerjual($dateRange) {
    global $config;
    $query = $config->prepare("SELECT COALESCE(SUM(dp.jumlah), 0) as total_barang_terjual 
        FROM detail_penjualan dp
        JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
        WHERE DATE(p.tanggal_input) BETWEEN ? AND ?");
    $query->bind_param('ss', $dateRange['start'], $dateRange['end']);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    return $row['total_barang_terjual'];
}

function getPenjualanBerdasarkanPeriode($dateRange) {
    global $config;
    $query = $config->prepare("SELECT 
        DATE(tanggal_input) as tanggal, 
        COALESCE(SUM(total), 0) as total_penjualan
    FROM penjualan
    WHERE DATE(tanggal_input) BETWEEN ? AND ?
    GROUP BY DATE(tanggal_input)
    ORDER BY tanggal");
    
    $query->bind_param('ss', $dateRange['start'], $dateRange['end']);
    $query->execute();
    $result = $query->get_result();
    
    $data = ['labels' => [], 'values' => []];
    
    $begin = new DateTime($dateRange['start']);
    $end = new DateTime($dateRange['end']);
    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($begin, $interval, $end->modify('+1 day'));
    
    $salesData = [];
    while ($row = $result->fetch_assoc()) {
        $salesData[$row['tanggal']] = $row['total_penjualan'];
    }
    
    foreach ($daterange as $date) {
        $formattedDate = $date->format('Y-m-d');
        $data['labels'][] = $formattedDate;
        $data['values'][] = $salesData[$formattedDate] ?? 0;
    }
    
    return $data;
}

function getBarangTerlaris($dateRange) {
    global $config;
    $query = $config->prepare("SELECT 
        b.nama_barang, 
        COALESCE(SUM(dp.jumlah), 0) as total_terjual
    FROM barang b
    LEFT JOIN detail_penjualan dp ON b.kodebarang = dp.kodebarang
    LEFT JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
    WHERE DATE(p.tanggal_input) BETWEEN ? AND ?
    GROUP BY b.kodebarang, b.nama_barang
    ORDER BY total_terjual DESC
    LIMIT 10");
    $query->bind_param('ss', $dateRange['start'], $dateRange['end']);
    $query->execute();
    $result = $query->get_result();
    
    $data = ['labels' => [], 'values' => []];
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['nama_barang'];
        $data['values'][] = $row['total_terjual'];
    }
    
    return $data;
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        $dateRange = getDateRange();
        $responseData = [
            'dateRange' => $dateRange,
            'totalBarang' => getTotal('barang'),
            'totalSupplier' => getTotal('supplier'),
            'penjualan' => getTotalPenjualan($dateRange),
            'barangTerjual' => getTotalBarangTerjual($dateRange),
            'stockByCategory' => getTotalStockByCategory(),
            'penjualanBerdasarkanPeriode' => getPenjualanBerdasarkanPeriode($dateRange),
            'barangTerlaris' => getBarangTerlaris($dateRange)
        ];

        echo json_encode($responseData);
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan saat memuat data.']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Baju</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style/dashboard.css" rel="stylesheet" type="text/css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
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
    grid-template-columns: repeat(4, 1fr); 
    gap: 15px; 
    margin-bottom: 40px;
}

.card {
    background-color: #ffffff;
    padding: 20px; 
    border-radius: 10px; 
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
    height: 400px;
}

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

.dashboard-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .dashboard-cards .card {
            flex: 1;
            margin: 0 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .chart-container {
            height: 350px;
        }
        .charts-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .charts-row > div {
            flex: 1;
        }
        .category-stock-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        #categoryStockChart {
    max-width: 100%;
    max-height: 300px; /* Batasi tinggi grafik */
    margin: 0 auto;
}


        .category-stock-legend {
            display : none;

        }

        .date-range-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .date-range-container label {
            margin-right: 10px;
            align-self: center;
        }
        #dateRange {
            width: 250px;
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
    <nav class="navbar">
        <div class="navbar-brand">Toko Baju</div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($_SESSION['user']['email']); ?></span>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <h1>Dashboard</h1>

        <div class="dashboard-cards">
            <div class="card" id="totalBarangCard">
                <div class="card-title">Total Barang</div>
                <div class="card-value" id="totalBarangValue">0</div>
            </div>
            <div class="card" id="totalSupplierCard">
                <div class="card-title">Total Supplier</div>
                <div class="card-value" id="totalSupplierValue">0</div>
            </div>
            <div class="card" id="penjualanCard">
                <div class="card-title">Pendapatan Hari Ini</div>
                <div class="card-value" id="penjualanValue">Rp 0</div>
            </div>
            <div class="card" id="barangTerjualCard">
                <div class="card-title">Barang Terjual</div>
                <div class="card-value" id="barangTerjualValue">0</div>
            </div>
        </div>

        <div class="date-range-container">
            <label>Rentang Tanggal:</label>
            <input type="text" id="dateRange" placeholder="Pilih Rentang Tanggal">
        </div>

        <div class="chart-row">
            <div class="chart-col">
                <div class="chart-container">
                    <h3>Pendapatan Berdasarkan Periode</h3>
                    <canvas id="periodChart"></canvas>
                </div>
            </div>
        </div>

        <div class="charts-row">
            <div>
                <div class="chart-container">
                    <h3>Stok per Kategori</h3>
                    <div class="category-stock-container">
                        <canvas id="categoryStockChart"></canvas>
                        <div id="categoryStockLegend" class="category-stock-legend"></div>
                    </div>
                </div>
            </div>
            <div>
                <div class="chart-container">
                    <h3>Barang Terlaris</h3>
                    <canvas id="topSellingItemsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const colors = [
            '#ff6347', '#f0ad4e', '#5bc0de', 
            '#5cb85c', '#d9534f', '#007bff',
            '#6c757d', '#28a745', '#dc3545', '#ffc107'
        ];

        const dateRangePicker = flatpickr("#dateRange", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [
                new Date(new Date().setMonth(new Date().getMonth() - 1)), 
                new Date()
            ],
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0].toISOString().split('T')[0];
                    const endDate = selectedDates[1].toISOString().split('T')[0];
                    fetchDashboardData(startDate, endDate);
                }
            }
        });

        let periodChart, categoryStockChart, topSellingItemsChart;

        function destroyCharts() {
            if (periodChart) periodChart.destroy();
            if (categoryStockChart) categoryStockChart.destroy();
            if (topSellingItemsChart) topSellingItemsChart.destroy();
        }

        function formatNumber(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        function fetchDashboardData(startDate, endDate) {
            const params = new URLSearchParams({ 
                start_date: startDate, 
                end_date: endDate 
            });

            fetch(`index.php?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                updateDashboardCards(data);
                updateCharts(data);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function updateDashboardCards(data) {
            document.getElementById('totalBarangValue').textContent = data.totalBarang;
            document.getElementById('totalSupplierValue').textContent = data.totalSupplier;
            document.getElementById('penjualanValue').textContent = `Rp ${formatNumber(data.penjualan)}`;
            document.getElementById('barangTerjualValue').textContent = data.barangTerjual;
        }

        function updateCharts(data) {
            destroyCharts();

            const periodCtx = document.getElementById('periodChart').getContext('2d');
            periodChart = new Chart(periodCtx, {
                type: 'line',
                data: {
                    labels: data.penjualanBerdasarkanPeriode.labels,
                    datasets: [{
                        label: 'Total Penjualan',
                        data: data.penjualanBerdasarkanPeriode.values,
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
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + formatNumber(value);
                                }
                            }
                        }
                    }
                }
            });

            // Category Stock Pie Chart
const categoryStockCtx = document.getElementById('categoryStockChart').getContext('2d');
categoryStockChart = new Chart(categoryStockCtx, {
    type: 'pie',
    data: {
        labels: data.stockByCategory.labels,
        datasets: [{
            data: data.stockByCategory.values,
            backgroundColor: colors.slice(0, data.stockByCategory.labels.length)
        }]
    },
    options: {
        responsive: true,
        plugins: { 
            legend: { 
                display: true, 
                position: 'top', // Menampilkan legenda di atas
            }
        },
        layout: {
            padding: 10 // Memberikan padding dalam chart
        }
    }
});

// Category Stock Legend tanpa jumlah stok
const legendContainer = document.getElementById('categoryStockLegend');
legendContainer.innerHTML = '';
data.stockByCategory.labels.forEach((label, index) => {
    const legendItem = document.createElement('div');
    legendItem.className = 'legend-item';
    
    const colorSpan = document.createElement('span');
    colorSpan.className = 'legend-color';
    colorSpan.style.backgroundColor = colors[index];
    
    const labelSpan = document.createElement('span');
    labelSpan.textContent = label; // Menampilkan hanya nama kategori tanpa jumlah stok
    
    legendItem.appendChild(colorSpan);
    legendItem.appendChild(labelSpan);
    legendContainer.appendChild(legendItem);
});


            // Top Selling Items Bar Chart
    const topSellingCtx = document.getElementById('topSellingItemsChart').getContext('2d');
    const topSellingChart = new Chart(topSellingCtx, {
        type: 'bar',
        data: {
            labels: data.barangTerlaris.labels,
            datasets: [{
                label: 'Jumlah Terjual',
                data: data.barangTerlaris.values,
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Terjual'
                    }
                }
            }
        }
    });
        }
        

        // Initial data load
        fetchDashboardData(
            new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0], 
            new Date().toISOString().split('T')[0]
        );
    });
    </script>
</body>
</html>