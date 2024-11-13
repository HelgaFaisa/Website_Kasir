<?php
session_start();

// Memastikan bahwa kasir sudah login
if (!isset($_SESSION['kasir'])) {
    header('Location: login.php'); // Arahkan ke halaman login jika kasir belum login
    exit;
}

// Koneksi ke database
include 'database.php'; // Pastikan ini sesuai dengan file koneksi Anda

// Menangani penambahan stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kode_barang']) && isset($_POST['jumlah'])) {
    $kode_barang = $_POST['kode_barang'];
    $jumlah = $_POST['jumlah'];

    // Menambahkan jumlah barang ke stok yang ada
    $sql = "UPDATE barang SET stok = stok + ? WHERE kode_barang = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jumlah, $kode_barang]);

    $_SESSION['message'] = 'Stok berhasil ditambah!';
    header('Location: restok.php');
    exit;
}

// Menampilkan daftar barang yang ada di stok
$sql = "SELECT * FROM barang";
$stmt = $pdo->query($sql);
$barang = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restok Barang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Styling untuk halaman restok */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin-left: 250px;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        button {
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .form-container {
            margin-top: 20px;
        }
        .form-container input {
            padding: 8px;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<h2>Restok Barang</h2>

<!-- Menampilkan pesan jika ada -->
<?php if (isset($_SESSION['message'])): ?>
    <div style="background-color: #28a745; color: white; padding: 10px; margin-bottom: 20px;">
        <?= $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<!-- Form untuk menambah stok -->
<div class="form-container">
    <h3>Tambah Stok Barang</h3>
    <form method="post" action="restok.php">
        <input type="text" name="kode_barang" placeholder="Kode Barang" required>
        <input type="number" name="jumlah" placeholder="Jumlah Stok" required min="1">
        <button type="submit">Tambah Stok</button>
    </form>
</div>

<!-- Daftar Barang yang ada di stok -->
<h3>Daftar Barang</h3>
<table>
    <thead>
        <tr>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Stok</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($barang as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['kode_barang']); ?></td>
                <td><?= htmlspecialchars($item['nama_barang']); ?></td>
                <td><?= htmlspecialchars($item['kategori']); ?></td>
                <td><?= htmlspecialchars($item['stok']); ?></td>
                <td>
                    <a href="restok.php?restok=<?= $item['kode_barang']; ?>" class="btn-hapus">Restok</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
