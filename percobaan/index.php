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
    $result = $config->query("SELECT SUM(jumlah) as total FROM penjualan");
    return $result->fetch_assoc()['total'] ?? 0;
}

// Fungsi untuk mendapatkan data penjualan per bulan
function getMonthlySalesData() {
    global $config;
    $query = "SELECT MONTH(tanggal_input) as bulan, COUNT(*) as jumlah_penjualan 
              FROM penjualan 
              WHERE YEAR(tanggal_input) = YEAR(CURDATE()) 
              GROUP BY bulan";
    $result = $config->query($query);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['bulan']] = $row['jumlah_penjualan'];
    }

    return $data;
}

$monthlySalesData = getMonthlySalesData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Tambahkan Chart.js -->
    <style>
        /* CSS Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .navbar {
            background-color: #800000;
            padding: 1rem;
            color: white;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .navbar-user a {
            color: white;
            text-decoration: none;
        }
        .sidebar {
            background-color: #2c3e50;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 60px;
            left: 0;
            padding-top: 1rem;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            padding: 1rem;
            display: block;
            transition: background-color 0.3s;
        }
        .sidebar-menu li a:hover {
            background-color: #34495e;
        }
        .main-content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 2rem;
            min-height: calc(100vh - 120px);
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .card-value {
            font-size: 2rem;
            font-weight: bold;
            color: #800000;
        }
        .footer {
            background-color: #800000;
            color: white;
            text-align: center;
            padding: 1rem;
            position: fixed;
            bottom: 0;
            width: calc(100% - 250px);
            margin-left: 250px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                overflow: hidden;
            }
            .sidebar-menu span {
                display: none;
            }
            .main-content, .footer {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
            .dashboard-cards {
                grid-template-columns: 1fr;
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

    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="barang.php"><i class="fas fa-tshirt"></i> <span>Barang</span></a></li>
            <li><a href="penjualan.php"><i class="fas fa-shopping-cart"></i> <span>Penjualan</span></a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> <span>Pelanggan</span></a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Laporan</span></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Pengaturan</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>Dashboard</h1>
        
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Stok Barang</div>
                <div class="card-value"><?php echo getTotal('barang'); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Penjualan</div>
                <div class="card-value">Rp <?php echo number_format(getDailySales(), 0, ',', '.'); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Jumlah Barang Terjual</div>
                <div class="card-value"><?php echo getTotalSoldItems(); ?></div>
            </div>
        </div>

        <!-- Grafik Bar Chart Penjualan Bulanan -->
        <h2>Grafik Penjualan Bulanan</h2>
        <canvas id="monthlySalesChart" width="400" height="200"></canvas>
    </div>

    <div class="footer">
        &copy; 2024 Toko Baju
    </div>

    <script>
        // Data untuk grafik bulanan
        const monthlySalesData = <?php echo json_encode($monthlySalesData); ?>;
        const labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
        const dataValues = Array(12).fill(0).map((_, index) => monthlySalesData[index + 1] || 0);

        // Buat grafik menggunakan Chart.js
        const ctx = document.getElementById('monthlySalesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Penjualan',
                    data: dataValues,
                    backgroundColor: 'rgba(128, 0, 0, 0.7)',
                    borderColor: 'rgba(128, 0, 0, 1)',
                    border
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>

