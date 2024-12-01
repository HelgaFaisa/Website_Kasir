<?php
require_once 'config.php'; // Menyertakan konfigurasi database

session_start();

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

try {
    // Query untuk mengambil data transaksi dengan urutan ASC (dari yang awal ke yang akhir)
    $sql = "SELECT id_penjualan, invoice, tanggal_input, total FROM penjualan ORDER BY tanggal_input ASC";
    $stmt = prepareQuery($sql); // Menggunakan fungsi dari config.php
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result(); // Mendapatkan hasil query
        $transaksi = $result->fetch_all(MYSQLI_ASSOC); // Mengambil data dalam format asosiatif
    } else {
        $transaksi = []; // Jika query gagal
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Transaksi</title>
    <style>
    /* Umum */
    /* Umum */
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Konten Utama */
.content {
    margin: 25px auto;
    padding: 40px;
    background-color: #fff;
    border-radius: 25px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    max-width: 1100px;
    width: calc(100% - 270px); /* Mengurangi lebar konten agar ada jarak dengan sidebar */
    display: block;
    box-sizing: border-box; /* Menambahkan box-sizing untuk padding */
    margin-left: 280px; /* Memberikan jarak minimal 10px dari sidebar (250px untuk sidebar 250px) */
    margin-right: 10px; /* Memberikan jarak 10px di sisi kanan */
}

/* Judul Halaman */
h1 {
    color: maroon;
    text-align: center;
    font-size: 32px;
    margin-top: 25px;
}

/* Tabel */
table {
    width: 100%;
    margin: 20px auto;
    border-collapse: collapse;
    background-color: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
    box-sizing: border-box;
}

th, td {
    padding: 12px;
    text-align: center;
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
    background-color: #ffe6e6;
}

/* Tombol Detail */
.btn-detail {
    background-color: maroon;
    color: white;
    border: none;
    padding: 8px 16px;
    cursor: pointer;
    font-weight: bold;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.btn-detail:hover {
    background-color: darkred;
}

.no-data {
    text-align: center;
    color: maroon;
    font-size: 18px;
}

/* Responsif untuk layar kecil */
@media (max-width: 768px) {
    body {
        padding: 0 10px;
        margin: 0;
    }

    .content {
        margin-left: 0;
        width: 100%;
        padding: 10px;
    }

    h1 {
        font-size: 28px;
    }

    table {
        width: 100%;
    }

    th, td {
        font-size: 14px; /* Ukuran font untuk perangkat kecil */
    }

    .btn-detail {
        width: 100%; /* Tombol mengambil lebar penuh pada perangkat kecil */
        padding: 10px;
    }

    /* Menyesuaikan jarak antar elemen pada perangkat kecil */
    table th, table td {
        padding: 10px 8px;
    }

    /* Membuat kolom 'Total' lebih kecil di perangkat kecil */
    table th:nth-child(4), table td:nth-child(4) {
        width: 20%; /* Menyesuaikan lebar kolom total */
    }
}

</style>
</head>
<body>
<?php include('sidebar.php'); ?>
    <!-- Konten Utama -->
    <div class="content">
        <h1>Daftar Transaksi</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transaksi)): ?>
                    <?php $counter = 1; // Inisialisasi penghitung ID ?>
                    <?php foreach ($transaksi as $row): ?>
                    <tr>
                        <td><?= $counter++; ?></td>
                        <td><?= htmlspecialchars($row['invoice']); ?></td>
                        <td><?= htmlspecialchars($row['tanggal_input']); ?></td>
                        <td><?= htmlspecialchars($row['total']); ?></td>
                        <td><button class="btn-detail" onclick="window.location.href='detail_transaksi.php?id=<?= htmlspecialchars($row['id_penjualan']); ?>'">Lihat Detail</button></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">Tidak ada data transaksi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
