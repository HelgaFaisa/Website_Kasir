<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Menangani aksi CRUD untuk pengembalian
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Menambahkan data pengembalian baru
        $id_restock = $_POST['id_restock'];
        $id_barang = $_POST['id_barang'];
        $id_supplier = $_POST['id_supplier'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah = $_POST['jumlah'];
        $kondisi_barang = $_POST['kondisi_barang'];
        $alasan_pengembalian = $_POST['alasan_pengembalian'];
        $status_pengembalian = $_POST['status_pengembalian'];
        $total_biaya_pengembalian = $_POST['total_biaya_pengembalian'];

        $stmt = $config->prepare("INSERT INTO pengembalian 
            (id_restock, id_barang, id_supplier, tanggal_pengembalian, jumlah, kondisi_barang, alasan_pengembalian, status_pengembalian, total_biaya_pengembalian) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisisssd", $id_restock, $id_barang, $id_supplier, $tanggal_pengembalian, $jumlah, $kondisi_barang, $alasan_pengembalian, $status_pengembalian, $total_biaya_pengembalian);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate data pengembalian
        $id_pengembalian = $_POST['id_pengembalian'];
        $id_restock = $_POST['id_restock'];
        $id_barang = $_POST['id_barang'];
        $id_supplier = $_POST['id_supplier'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah = $_POST['jumlah'];
        $kondisi_barang = $_POST['kondisi_barang'];
        $alasan_pengembalian = $_POST['alasan_pengembalian'];
        $status_pengembalian = $_POST['status_pengembalian'];
        $total_biaya_pengembalian = $_POST['total_biaya_pengembalian'];

        $stmt = $config->prepare("UPDATE pengembalian SET id_restock = ?, id_barang = ?, id_supplier = ?, tanggal_pengembalian = ?, jumlah = ?, kondisi_barang = ?, alasan_pengembalian = ?, status_pengembalian = ?, total_biaya_pengembalian = ? WHERE id = ?");
        $stmt->bind_param("iiisisssdi", $id_restock, $id_barang, $id_supplier, $tanggal_pengembalian, $jumlah, $kondisi_barang, $alasan_pengembalian, $status_pengembalian, $total_biaya_pengembalian, $id_pengembalian);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus data pengembalian
        $id_pengembalian = $_POST['id_pengembalian'];

        $stmt = $config->prepare("DELETE FROM pengembalian WHERE id = ?");
        $stmt->bind_param("i", $id_pengembalian);
        $stmt->execute();
        $stmt->close();
    }
}

// Proses pencarian
$search_term = '';
if (isset($_POST['search'])) {
    $search_term = $_POST['search_term'];
    $pengembalian_query = "SELECT * FROM pengembalian WHERE id_barang LIKE ? OR id_supplier LIKE ? OR alasan_pengembalian LIKE ?";
    $stmt = $config->prepare($pengembalian_query);
    $search_like = "%$search_term%";
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
    $stmt->execute();
    $pengembalian_result = $stmt->get_result();
    $stmt->close();
} else {
    $pengembalian_query = "SELECT * FROM pengembalian";
    $pengembalian_result = $config->query($pengembalian_query);
}

$barang_query = "SELECT * FROM barang";
$barang_result = $config->query($barang_query);

$supplier_query = "SELECT * FROM supplier";
$supplier_result = $config->query($supplier_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styling untuk halaman pengembalian */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
        }

        h1, h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            grid-gap: 15px;
        }

        label {
            font-size: 14px;
            color: #333;
            align-self: center;
            margin-bottom: 5px;
        }

        input[type="number"], input[type="date"], textarea, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
        }

        button {
            grid-column: span 2;
            padding: 6px 12px; /* Tombol lebih kecil */
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #e0e0e0;
        }

        .button {
            padding: 6px 12px; /* Tombol lebih kecil */
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px; /* Ukuran font lebih kecil */
            transition: background-color 0.3s;
        }

        .btn-add {
            background-color: #4CAF50;
            color: white;
        }

        .btn-add:hover {
            background-color: #45a049;
        }

        .btn-update {
            background-color: #007bff;
            color: white;
        }

        .btn-update:hover {
            background-color: #0056b3;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
        }

        .btn-search {
            padding: 6px 12px; /* Tombol pencarian lebih kecil */
            background-color: #008CBA;
            color: white;
            font-size: 14px; /* Ukuran font lebih kecil */
        }

        .btn-search:hover {
            background-color: #007B8B;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Data Pengembalian Barang</h1>

        <!-- Form Pencarian -->
        <form method="POST" action="pengembalian.php">
            <input type="text" name="search_term" value="<?= $search_term ?>" placeholder="Cari..." style="width: 60%; padding: 8px;">
            <button type="submit" name="search" class="button btn-search">Cari</button>
        </form>

        <form method="POST" action="pengembalian.php">
            <label for="id_restock">ID Restock:</label>
            <input type="number" id="id_restock" name="id_restock" required>

            <label for="id_barang">ID Barang:</label>
            <select name="id_barang" id="id_barang" required>
                <?php while ($barang = $barang_result->fetch_assoc()): ?>
                    <option value="<?= $barang['id'] ?>"><?= $barang['nama_barang'] ?></option>
                <?php endwhile; ?>
            </select>

            <label for="id_supplier">ID Supplier:</label>
            <select name="id_supplier" id="id_supplier" required>
                <?php while ($supplier = $supplier_result->fetch_assoc()): ?>
                    <option value="<?= $supplier['id'] ?>"><?= $supplier['nama_supplier'] ?></option>
                <?php endwhile; ?>
            </select>

            <label for="tanggal_pengembalian">Tanggal Pengembalian:</label>
            <input type="date" id="tanggal_pengembalian" name="tanggal_pengembalian" required>

            <label for="jumlah">Jumlah:</label>
            <input type="number" id="jumlah" name="jumlah" required>

            <label for="kondisi_barang">Kondisi Barang:</label>
            <textarea id="kondisi_barang" name="kondisi_barang" rows="3" required></textarea>

            <label for="alasan_pengembalian">Alasan Pengembalian:</label>
            <textarea id="alasan_pengembalian" name="alasan_pengembalian" rows="3" required></textarea>

            <label for="status_pengembalian">Status Pengembalian:</label>
            <select name="status_pengembalian" id="status_pengembalian" required>
                <option value="proses">Proses</option>
                <option value="selesai">Selesai</option>
            </select>

            <label for="total_biaya_pengembalian">Total Biaya Pengembalian:</label>
            <input type="number" id="total_biaya_pengembalian" name="total_biaya_pengembalian" step="0.01" required>

            <button type="submit" name="submit" class="button btn-add">Tambah</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID Pengembalian</th>
                    <th>ID Restock</th>
                    <th>ID Barang</th>
                    <th>ID Supplier</th>
                    <th>Tanggal Pengembalian</th>
                    <th>Jumlah</th>
                    <th>Kondisi Barang</th>
                    <th>Alasan Pengembalian</th>
                    <th>Status</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pengembalian_result->num_rows > 0): ?>
                    <?php while ($row = $pengembalian_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['id_restock'] ?></td>
                            <td><?= $row['id_barang'] ?></td>
                            <td><?= $row['id_supplier'] ?></td>
                            <td><?= $row['tanggal_pengembalian'] ?></td>
                            <td><?= $row['jumlah'] ?></td>
                            <td><?= $row['kondisi_barang'] ?></td>
                            <td><?= $row['alasan_pengembalian'] ?></td>
                            <td><?= $row['status_pengembalian'] ?></td>
                            <td><?= number_format($row['total_biaya_pengembalian'], 2) ?></td>
                            <td>
                                <form method="POST" action="pengembalian.php" style="display: inline;">
                                    <input type="hidden" name="id_pengembalian" value="<?= $row['id'] ?>">
                                    <button type="submit" name="update" class="button btn-update">Update</button>
                                    <button type="submit" name="delete" class="button btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center;">Tidak ada data pengembalian</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
