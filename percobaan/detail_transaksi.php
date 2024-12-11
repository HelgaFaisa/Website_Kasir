<?php
require_once 'config.php'; // Menyertakan konfigurasi database

session_start();

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Memastikan ID diterima dalam URL dan valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_penjualan = $_GET['id']; // Mendapatkan ID transaksi dari URL
} else {
    die("ID transaksi tidak valid.");
}

try {
    // Query untuk data transaksi dari tabel penjualan
    $sql_penjualan = "SELECT invoice, tanggal_input, total, diskon, bayar, kembali FROM penjualan WHERE id_penjualan = ?";
    $stmt_penjualan = prepareQuery($sql_penjualan); // Menyiapkan query
    $stmt_penjualan->bind_param("i", $id_penjualan); // Mengikat parameter ID
    $stmt_penjualan->execute(); // Menjalankan query
    $result_penjualan = $stmt_penjualan->get_result(); // Mendapatkan hasil query
    $transaksi = $result_penjualan->fetch_assoc(); // Mengambil data dalam format asosiatif

    // Query untuk detail barang pada transaksi
    $sql_detail = "SELECT kodebarang, jumlah, harga, total FROM detail_penjualan WHERE id_penjualan = ?";
    $stmt_detail = prepareQuery($sql_detail); // Menyiapkan query
    $stmt_detail->bind_param("i", $id_penjualan); // Mengikat parameter ID
    $stmt_detail->execute(); // Menjalankan query
    $result_detail = $stmt_detail->get_result(); // Mendapatkan hasil query
    $detail_barang = $result_detail->fetch_all(MYSQLI_ASSOC); // Mengambil data detail barang

} catch (Exception $e) {
    die("Error: " . $e->getMessage()); // Menangani error jika terjadi masalah dengan database
}

// Memastikan data transaksi ditemukan
if (!$transaksi) {
    die("Transaksi tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi</title>
    <style>
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    color: #333;
    display: flex; /* Flexbox untuk tata letak sidebar dan konten */
    flex-wrap: wrap; /* Menyesuaikan tata letak pada layar kecil */
}

/* Konten Utama */
.content {
    margin-left: 280px; /* Memberikan sedikit jarak tambahan dari sidebar */
    padding: 40px;
    background-color: #fff;
    border-radius: 25px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    max-width: 100%; /* Lebar konten disesuaikan */
    width: calc(100% - 270px); /* Disesuaikan dengan margin baru */
    display: block;
    padding-top: 20px; /* Memberikan jarak pada bagian atas konten */
    margin-right: 10px;
}


/* Judul */
h1 {
    color: maroon;
    text-align: center;
    font-size: 32px;
}

/* Sub-Judul */
h2 {
    color: maroon;
    font-size: 24px;
    margin-top: 20px;
    border-bottom: 2px solid maroon;
    padding-bottom: 5px;
}

/* Tabel */
table {
    width: 100%;
    margin: 20px 0;
    border-collapse: collapse;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: maroon;
    color: white;
    text-transform: uppercase;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #f1f1f1;
}

/* Tombol Kembali */
.back-button {
    background-color: maroon;
    color: white;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    font-weight: bold;
    border-radius: 4px;
    display: inline-block;
    margin: 20px 0;
    text-align: center;
}

.back-button:hover {
    background-color: darkred;
}

.no-data {
    text-align: center;
    color: maroon;
    font-size: 18px;
    padding: 20px;
}

/* Responsif untuk layar kecil */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }

    #sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .content {
        margin-left: 0; /* Menghapus margin jika tidak ada sidebar */
        width: 100%; /* Konten mengambil lebar penuh */
        padding: 10px;
    }

    table {
        width: 100%; /* Menyesuaikan lebar tabel untuk perangkat kecil */
    }

    th, td {
        font-size: 14px; /* Ukuran font lebih kecil untuk perangkat kecil */
    }

    h1 {
        font-size: 28px; /* Ukuran font judul lebih kecil di perangkat kecil */
    }

    h2 {
        font-size: 20px; /* Ukuran font sub-judul lebih kecil di perangkat kecil */
    }

    .back-button {
        width: 100%; /* Tombol kembali mengambil lebar penuh */
    }
}
    </style>
</head>
<body>
<?php include('sidebar.php'); ?>
<div class="content">
    <h1>Detail Transaksi</h1>
    
    <!-- Informasi Umum Transaksi -->
    <h2>Informasi Umum Transaksi</h2>
    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
        <tr>
                <td><i class="fas fa-barcode"></i><strong>  Invoice</strong></td>
                <td><?= htmlspecialchars($transaksi['invoice']); ?></td>
            </tr>
            <tr>
                <td><i class="fas fa-calendar-alt"></i><strong>  Tanggal</strong></td>
                <td><?= htmlspecialchars($transaksi['tanggal_input']); ?></td>
            </tr>
            <tr>
                <td><i class="fas fa-percent"></i><strong>  Diskon</strong></td>
                <td><?= htmlspecialchars($transaksi['diskon']); ?></td>
            </tr>
            <tr>
                <td><i class="fas fa-money-bill-wave"></i><strong>  Total Bayar</strong></td>
                <td><?= htmlspecialchars($transaksi['total']); ?></td>
            </tr>
            <tr>
                <td><i class="fas fa-credit-card"></i><strong>  Bayar</strong></td>
                <td><?= htmlspecialchars($transaksi['bayar']); ?></td>
            </tr>
            <tr>
                <td><i class="fas fa-coins"></i><strong>  Kembalian</strong></td>
                <td><?= htmlspecialchars($transaksi['kembali']); ?></td>
            </tr>
        </tbody>
    </table>
    
    <!-- Detail Barang -->
    <h2><i class="fas fa-shopping-basket"></i> Detail Barang</h2>
    <table>
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($detail_barang)): ?>
                <?php foreach ($detail_barang as $detail): ?>
                    <tr>
                        <td><?= htmlspecialchars($detail['kodebarang']); ?></td>
                        <td><?= htmlspecialchars($detail['jumlah']); ?></td>
                        <td><?= htmlspecialchars($detail['harga']); ?></td>
                        <td><?= htmlspecialchars($detail['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data"><i class="fas fa-exclamation-triangle"></i> Tidak ada detail barang untuk transaksi ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Tombol Kembali -->
    <button class="back-button" onclick="goBack()">
        <i class="fas fa-arrow-left"></i> Kembali
    </button>
</div>

<script>
    // Fungsi untuk mengarahkan kembali ke daftar transaksi
    function goBack() {
        window.location.href = 'transaksi1.php'; // Mengarahkan ke halaman daftar transaksi
    }
</script>
</body>
</html>
