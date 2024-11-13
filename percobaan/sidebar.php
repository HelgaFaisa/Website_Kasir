<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar dengan Submenu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Styling untuk Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            color: white;
            padding-top: 20px;
            z-index: 9999; /* Pastikan sidebar berada di atas elemen lainnya */
        }
        .sidebar .sidebar-menu {
            list-style-type: none;
            padding: 0;
        }
        .sidebar .sidebar-menu li {
            padding: 10px;
            border-bottom: 1px solid #444;
        }
        .sidebar .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 5px;
        }
        .sidebar .sidebar-menu li a:hover {
            background-color: #575757;
        }
        /* Styling untuk Submenu */
        .submenu {
            display: none; /* Disembunyikan secara default */
            list-style-type: none;
            padding-left: 20px;
            background-color: #444; /* Warna background submenu */
        }
        .submenu li {
            padding: 8px 10px;
            border-bottom: 1px solid #555;
        }
        .submenu li a {
            color: #ddd; /* Warna teks submenu */
        }
        .submenu li a:hover {
            background-color: #666;
        }
        /* Menampilkan submenu ketika menu-toggle diaktifkan */
        .menu-toggle.active .submenu {
            display: block;
        }
    </style>
</head>
<body>

<div class="sidebar">
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
                <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan Penjualan</a></li>
            </ul>
        </li>
        
        <li><a href="restok.php"><i class="fas fa-box"></i> Restok</a></li>
        <li><a href="pengembalian.php"><i class="fas fa-undo"></i> Pengembalian</a></li>
        <li><a href="pengaturan.php"><i class="fas fa-cogs"></i> Pengaturan Toko</a></li>
    </ul>
</div>

<!-- JavaScript untuk Toggle Submenu -->
<script>
    // Mendapatkan semua elemen dengan kelas 'menu-toggle'
    document.querySelectorAll('.menu-toggle').forEach(function(menu) {
        menu.addEventListener('click', function() {
            // Menutup submenu lain yang sedang terbuka
            document.querySelectorAll('.menu-toggle').forEach(function(otherMenu) {
                if (otherMenu !== menu) otherMenu.classList.remove('active');
            });
            // Toggle untuk menampilkan submenu
            menu.classList.toggle('active');
        });
    });
</script>

</body>
</html>
