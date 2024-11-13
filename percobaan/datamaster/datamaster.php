<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Master - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styling untuk sidebar dan halaman */
        /* Gunakan kode CSS sebelumnya, atau sesuaikan sesuai kebutuhan */
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="datamaster.php"><i class="fas fa-cogs"></i> Data Master</a>
                <ul>
                    <li><a href="barang.php">Barang</a></li>
                    <li><a href="kategori.php">Kategori</a></li>
                    <li><a href="supplier.php">Supplier</a></li>
                </ul>
            </li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> Transaksi</a></li>
            <!-- Tambahkan menu lainnya sesuai kebutuhan -->
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Data Master</h1>
        <ul>
            <li><a href="barang.php">Barang</a></li>
            <li><a href="kategori.php">Kategori</a></li>
            <li><a href="supplier.php">Supplier</a></li>
        </ul>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; 2024 Toko Baju
    </div>
</body>
</html>
