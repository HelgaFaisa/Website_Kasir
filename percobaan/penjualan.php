<?php
session_start();

// Memastikan variabel 'keranjang' ada di dalam sesi
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = []; // Inisialisasi keranjang sebagai array kosong
}

// Menambahkan item ke dalam keranjang jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kode_barang'])) {
    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $total = $jumlah * $harga;

    $_SESSION['keranjang'][] = [
        'kode_barang' => $kode_barang,
        'nama_barang' => $nama_barang,
        'jumlah' => $jumlah,
        'harga' => $harga,
        'total' => $total,
        'kasir' => $_SESSION['kasir'] // Asumsikan kasir login
    ];
}

// Menghapus item dari keranjang
if (isset($_GET['hapus'])) {
    $index = $_GET['hapus'];
    unset($_SESSION['keranjang'][$index]);
    $_SESSION['keranjang'] = array_values($_SESSION['keranjang']); // Reindex array
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kasir - Toko Baju</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #333;
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
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
        }
        .sidebar .sidebar-menu li a:hover {
            background-color: #575757;
        }
        .submenu {
            display: none;
            list-style-type: none;
            padding-left: 20px;
            background-color: #444;
        }
        .menu-toggle.active .submenu {
            display: block;
        }
        .container {
            margin-left: 260px;
            padding: 20px;
            width: 80%;
        }
        form input, form button {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        form button {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        form button:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .btn-hapus {
            color: #dc3545;
            text-decoration: none;
        }
        .btn-hapus:hover {
            text-decoration: underline;
        }
        .current-time {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            color: #007bff;
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

<div class="container">
    <h2>Form Kasir</h2>
    <div class="current-time" id="current-time"></div>

    <form method="post" action="">
        <input type="text" name="kode_barang" placeholder="Kode Barang" required>
        <input type="text" name="nama_barang" placeholder="Nama Barang" required>
        <input type="number" name="jumlah" placeholder="Jumlah" required min="1">
        <input type="number" name="harga" placeholder="Harga" required min="0">
        <button type="submit">Tambah ke Keranjang</button>
    </form>

    <h3>Keranjang Belanja</h3>
    <table>
        <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Jumlah</th>
            <th>Total</th>
            <th>Kasir</th>
            <th>Aksi</th>
        </tr>
        <?php if (!empty($_SESSION['keranjang'])): ?>
            <?php foreach ($_SESSION['keranjang'] as $index => $item): ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= htmlspecialchars($item['nama_barang']); ?></td>
                    <td><?= htmlspecialchars($item['jumlah']); ?></td>
                    <td><?= number_format($item['total'], 2); ?></td>
                    <td><?= htmlspecialchars($item['kasir']); ?></td>
                    <td><a href="?hapus=<?= $index; ?>" class="btn-hapus">Hapus</a></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">Keranjang Kosong</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<script>
    // Fungsi untuk memperbarui waktu setiap detik
    function updateTime() {
        var now = new Date();
        var hours = now.getHours().toString().padStart(2, '0');
        var minutes = now.getMinutes().toString().padStart(2, '0');
        var seconds = now.getSeconds().toString().padStart(2, '0');
        var date = now.toLocaleDateString('id-ID'); // Format tanggal Indonesia
        document.getElementById('current-time').textContent = date + ' ' + hours + ':' + minutes + ':' + seconds;
    }

    // Update waktu setiap detik
    setInterval(updateTime, 1000);

    // Jalankan sekali di awal untuk menampilkan waktu langsung
    updateTime();
</script>

</body>
</html>
