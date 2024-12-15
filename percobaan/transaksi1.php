<?php 
require_once 'config.php'; // Menyertakan konfigurasi database

session_start();

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Inisialisasi variabel $searchKeyword
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Query untuk mengambil data transaksi dengan filter pencarian
    $sql = "SELECT id_penjualan, invoice, tanggal_input, total FROM penjualan";
    
    if (!empty($searchKeyword)) {
        $sql .= " WHERE invoice LIKE ? OR tanggal_input LIKE ?";
    }

    $sql .= " ORDER BY tanggal_input ASC";
    $stmt = prepareQuery($sql); // Menggunakan fungsi dari config.php

    if ($stmt) {
        if (!empty($searchKeyword)) {
            $likeKeyword = '%' . $searchKeyword . '%';
            $stmt->bind_param('ss', $likeKeyword, $likeKeyword);
        }
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
    /* Gaya CSS */
    body {
        font-family: 'Roboto', 'Arial', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .content {
        margin: 25px auto;
        padding: 40px;
        background-color: #fff;
        border-radius: 25px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        max-width: 1100px;
        width: calc(100% - 270px);
        margin-left: 280px;
        margin-right: 10px;
    }

    h1 {
        color: #800000;
        text-align: center;
        font-size: 32px;
        margin-top: 25px;
        border-bottom: 3px solid #800000;
        padding-bottom: 10px;
    }

    .search-container {
        margin-bottom: 20px;
        position: relative;
        width: 100%;
        max-width: 400px;
       
    }

    .search-container i {
        position: absolute;
        top: 50%;
        left: 10px;
        transform: translateY(-50%);
        color: #800000;
        font-size: 18px;
    }

    .search-container input {
        width: 100%;
    max-width: 300px; /* Sesuaikan ukuran maksimal input pencarian */
    padding: 10px 10px 10px 35px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    }

    .search-container input:focus {
        border-color: #800000;
        outline: none;
        box-shadow: 0 4px 12px rgba(128, 0, 0, 0.2);
    }

    table {
        width: 100%;
        margin: 20px auto;
        border-collapse: separate;
        border-spacing: 0;
        background-color: white;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        overflow: hidden;
    }

    thead {
        background-color: #800000;
        color: white;
    }

    th, td {
        padding: 15px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
        transition: background-color 0.3s ease;
    }

    .btn-detail {
        background-color: #800000;
        color: white;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
        font-weight: bold;
        border-radius: 20px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        gap: 8px;
    }

    .btn-detail:hover {
        background-color: #600000;
        transform: scale(1.05);
    }

    .no-data {
        text-align: center;
        color: #800000;
        font-size: 18px;
        padding: 20px;
    }

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
            padding: 10px;
            font-size: 14px;
        }

        .btn-detail {
            width: 100%;
            padding: 10px;
        }

        table th:nth-child(4), table td:nth-child(4) {
            width: 20%;
        }
    }
    </style>
</head>
<body>
<?php include('sidebar.php'); ?>

<div class="content">
    <h1><i class="fas fa-list"></i> Daftar Transaksi</h1>

    <div class="search-container">
        <i class="fas fa-search"></i>
        <form method="get" action="">
            <input type="text" name="search" placeholder="Cari data..." value="<?= htmlspecialchars($searchKeyword); ?>">
        </form>
    </div>

    <table id="dataTable">
        <thead>
            <tr>
                <th>No</th>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($transaksi)): ?>
                <?php $counter = 1; ?>
                <?php foreach ($transaksi as $row): ?>
                    <tr>
                        <td><?= $counter++; ?></td>
                        <td><?= htmlspecialchars($row['invoice']); ?></td>
                        <td><?= htmlspecialchars($row['tanggal_input']); ?></td>
                        <td><?= htmlspecialchars($row['total']); ?></td>
                        <td>
                            <button class="btn-detail" onclick="window.location.href='detail_transaksi.php?id=<?= htmlspecialchars($row['id_penjualan']); ?>'">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </button>
                        </td>
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
