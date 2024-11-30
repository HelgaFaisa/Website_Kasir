<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard dengan Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Amoresa&display=swap" rel="stylesheet">
    <link href="style/sidebar.css" rel="stylesheet" type="text/css">
</head>
<body>

<!-- Tombol Menu untuk Mobile -->
<button class="menu-toggle-button" onclick="toggleSidebar()">
    <i class="fas fa-ellipsis-v"></i> <!-- Titik tiga untuk toggle -->
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Header Sidebar dengan Logo -->
    <div class="sidebar-header">
        <img src="img/logo.png" alt="Logo Toko">
        <h3>AdaAllShop</h3>
    </div>

    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

        <li class="menu-toggle">
            <a href="javascript:void(0)"><i class="fas fa-cogs"></i> Data Master</a>
            <ul class="submenu">
                <li><a href="barang.php"><i class="fas fa-tshirt"></i> Barang</a></li>
                <li><a href="kategori.php"><i class="fas fa-th"></i> Kategori</a></li>
                <li><a href="supplier.php"><i class="fas fa-truck"></i> Supplier</a></li>
            </ul>
        </li>

        <li class="menu-toggle">
            <a href="javascript:void(0)"><i class="fas fa-exchange-alt"></i> Transaksi</a>
            <ul class="submenu">
                <li><a href="penjualan.php"><i class="fas fa-shopping-cart"></i> Penjualan</a></li>
                <li><a href="laporan_penjualan.php"><i class="fas fa-file-alt"></i> Laporan Penjualan</a></li>
            </ul>
        </li>

        <li class="menu-toggle">
            <a href="javascript:void(0)"><i class="fas fa-sync-alt"></i> Restok</a>
            <ul class="submenu">
                <li><a href="restok.php"><i class="fas fa-file-alt"></i> Restok</a></li>
                <li><a href="laporan_pembelian.php"><i class="fas fa-file-alt"></i> Laporan Pembelian</a></li>
            </ul>
        </li>

        <li><a href="pengembalian.php"><i class="fas fa-undo"></i> Pengembalian</a></li>
    </ul>
</div>

<!-- Kontainer untuk menampilkan konten -->
<div class="content-container" id="content-container">
    <!-- Konten akan dimuat di sini -->
</div>

<!-- JavaScript untuk Toggle Submenu dan Sidebar -->
<script>
    // Fungsi untuk toggle submenu
    document.querySelectorAll('.menu-toggle').forEach(function(menu) {
        menu.addEventListener('click', function() {
            if (menu.classList.contains('active')) {
                menu.classList.remove('active');
            } else {
                document.querySelectorAll('.menu-toggle').forEach(function(otherMenu) {
                    otherMenu.classList.remove('active');
                });
                menu.classList.add('active');
            }
        });
    });

    // Fungsi untuk toggle sidebar
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }

    // AJAX untuk memuat konten halaman log_aktivitas.php
    document.querySelectorAll('[data-page]').forEach(link => {
        link.addEventListener('click', function() {
            const page = this.getAttribute('data-page');
            fetch(page)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('content-container').innerHTML = data;
                })
                .catch(error => console.error('Error:', error));
        });
    });
</script>

</body>
</html>
