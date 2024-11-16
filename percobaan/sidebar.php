<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard dengan Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Styling untuk Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #800000;
            color: white;
            padding-top: 20px;
            z-index: 9999;
            transition: transform 0.3s ease;
        }

        .sidebar-menu {
            list-style-type: none;
            padding: 0;
        }

        .sidebar-menu li {
            padding: 10px;
            border-bottom: 1px solid #444;
        }

        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 5px;
        }

        .sidebar-menu li a:hover {
            background-color: #CD3F3E;
        }

        .submenu {
            display: none;
            list-style-type: none;
            padding-left: 20px;
            background-color: #800000;
            margin-top: 5px;
        }

        .submenu li {
            padding: 8px 10px;
            border-bottom: 1px solid #555;
        }

        .submenu li a {
            color: #ddd;
        }

        .submenu li a:hover {
            background-color: #CD3F3E;
        }

        .menu-toggle.active .submenu {
            display: block;
        }

        .menu-toggle-button {
            display: none;
            font-size: 24px;
            color: white;
            background-color: #333;
            padding: 10px;
            border: none;
            cursor: pointer;
            position: absolute;
            top: 20px;
            left: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
                width: 250px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .menu-toggle-button {
                display: block;
            }
        }

        /* Styling untuk Kontainer Konten */
        .content-container {
            margin-left: 260px;
            padding: 20px;
        }
    </style>
</head>
<body>

<!-- Tombol Menu untuk Mobile -->
<button class="menu-toggle-button" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
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
                <li><a href="laporan_penjualan.php"><i class="fas fa-file-alt"></i> Laporan Penjualan</a></li>
            </ul>
        </li>

        <!-- Menu Restok dan Submenu Laporan Pembelian -->
        <li class="menu-toggle">
            <a href="javascript:void(0)"><i class="fas fa-sync-alt"></i> Restok</a>
            <ul class="submenu">
                <li><a href="restok.php"><i class="fas fa-file-alt"></i> Restok</a></li>
                <li><a href="laporan_pembelian.php"><i class="fas fa-file-alt"></i> Laporan Pembelian</a></li>
            </ul>
        </li>

        <li><a href="pengembalian.php"><i class="fas fa-undo"></i> Pengembalian</a></li>


        <li class="menu-toggle">
            <a href="javascript:void(0)"><i class="fas fa-cogs"></i> Pengaturan Toko</a>
            <ul class="submenu">
                <li><a href="javascript:void(0)" data-page="log_aktivitas.php"><i class="fas fa-history"></i> Log Aktivitas</a></li>
            </ul>
        </li>
    </ul>
</div>

<!-- Kontainer untuk menampilkan konten yang dimuat melalui AJAX -->
<div class="content-container" id="content-container">
    <!-- Konten akan dimuat di sini -->
</div>

<!-- JavaScript untuk Toggle Submenu dan Sidebar -->
<script>
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

    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
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
