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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Reset and general styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body and background */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
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
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: #f8f9fa;
        }

        .navbar-user {
            display: flex;
            align-items: center;
        }

        .navbar-user a {
            color: #ffffff;
            text-decoration: none;
            margin-left: 15px;
            font-size: 1.1rem;
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
            transition: background-color 0.3s ease-in-out;
            border-bottom: 1px solid #484848;
        }

        .sidebar-menu li a:hover {
            background-color: #0062cc;
        }

        /* Submenu Styling (Dropdown) */
        .submenu {
            display: none;
            list-style: none;
            padding-left: 30px;
        }

        .submenu li a {
            padding: 12px 20px;
            font-size: 0.9rem;
        }

        .sidebar-menu li.active > .submenu {
            display: block;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            margin-top: 80px;
            padding: 30px;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }

        /* Footer Styling */
        .footer {
            background-color: #343a40;
            color: #f8f9fa;
            text-align: center;
            padding: 20px;
            position: fixed;
            bottom: 0;
            width: calc(100% - 250px);
            margin-left: 250px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }

            .sidebar-menu li a {
                padding: 12px;
                text-align: center;
            }

            .main-content {
                margin-left: 60px;
                width: calc(100% - 60px);
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .navbar {
                flex-direction: column;
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

        <!-- Chart -->
        <canvas id="salesChart"></canvas>
    </div>


    <script>
    // Toggle Submenu for Data Master and Transaksi
    document.querySelectorAll('.menu-toggle').forEach(function(menu) {
        menu.addEventListener('click', function() {
            this.classList.toggle('active');
            let submenu = this.querySelector('.submenu');
            if (submenu.style.display === 'block') {
                submenu.style.display = 'none';
            } else {
                submenu.style.display = 'block';
            }
        });
    });

    // Chart.js configuration for Monthly Sales Data
    const ctx = document.getElementById('salesChart').getContext('2d');
    const monthlySalesData = <?php echo json_encode($monthlySalesData); ?>;

    // Nama bulan dalam Bahasa Indonesia
    const monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    const labels = monthNames; // Menggunakan nama bulan
    const data = Object.values(monthlySalesData); // Data penjualan per bulan

    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Penjualan Bulanan',
                data: data,
                borderColor: '#007bff',
                fill: false,
                tension: 0.1
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
