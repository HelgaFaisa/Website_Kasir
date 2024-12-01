<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';



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

        <iframe 
    title="Dashboard22" 
    width="1920" 
    height="810" 
    src="https://app.powerbi.com/reportEmbed?reportId=8eb2cf54-f693-4df7-afc5-6cc799a91a16&autoAuth=true&ctid=5263cc81-5912-42c4-abc1-d0f1b668b530" 
    frameborder="0" 
    allowFullScreen="true">
    
</iframe>
</body>
</html>