<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Fungsi untuk menangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Menambahkan barang baru
        $nama_barang = $_POST['nama_barang'];
        $kategori_id = $_POST['kategori_id'];
        $supplier_id = $_POST['supplier_id'];
        $harga = $_POST['harga'];

        $stmt = $config->prepare("INSERT INTO barang (nama_barang, kategori_id, supplier_id, harga) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama_barang, $kategori_id, $supplier_id, $harga);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate barang
        $id_barang = $_POST['id_barang'];
        $nama_barang = $_POST['nama_barang'];
        $kategori_id = $_POST['kategori_id'];
        $supplier_id = $_POST['supplier_id'];
        $harga = $_POST['harga'];

        $stmt = $config->prepare("UPDATE barang SET nama_barang = ?, kategori_id = ?, supplier_id = ?, harga = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nama_barang, $kategori_id, $supplier_id, $harga, $id_barang);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus barang
        $id_barang = $_POST['id_barang'];

        $stmt = $config->prepare("DELETE FROM barang WHERE id = ?");
        $stmt->bind_param("i", $id_barang);
        $stmt->execute();
        $stmt->close();
    }
}

$barang_query = "SELECT * FROM barang";
$barang_result = $config->query($barang_query);

$kategori_query = "SELECT * FROM kategori";
$kategori_result = $config->query($kategori_query);

$supplier_query = "SELECT * FROM supplier";
$supplier_result = $config->query($supplier_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styling untuk halaman barang */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
        }

        .btn-add {
            background-color: #4CAF50;
            color: white;
        }

        .btn-update {
            background-color: #007bff;
            color: white;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .btn-sort {
            background-color: #ffa500;
            color: white;
        }

        .btn-refresh {
            background-color: #008CBA;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <h1>Data Barang</h1>

        <form method="POST" action="barang.php">
            <label for="nama_barang">Nama Barang:</label>
            <input type="text" id="nama_barang" name="nama_barang" required><br>

            <label for="kategori_id">Kategori:</label>
            <select name="kategori_id" id="kategori_id" required>
                <?php while ($kategori = $kategori_result->fetch_assoc()): ?>
                    <option value="<?= $kategori['id']; ?>"><?= $kategori['nama_kategori']; ?></option>
                <?php endwhile; ?>
            </select><br>

            <label for="supplier_id">Supplier:</label>
            <select name="supplier_id" id="supplier_id" required>
                <?php while ($supplier = $supplier_result->fetch_assoc()): ?>
                    <option value="<?= $supplier['id']; ?>"><?= $supplier['nama_supplier']; ?></option>
                <?php endwhile; ?>
            </select><br>

            <label for="harga">Harga:</label>
            <input type="number" id="harga" name="harga" required><br>

            <button type="submit" name="submit" class="button btn-add">Tambah Barang</button>
        </form>

        <h2>Daftar Barang</h2>

        <table>
            <tr>
                <th>No</th>
                <th>ID Barang</th>
                <th>Kategori</th>
                <th>Nama Barang</th>
                <th>Stok</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Satuan</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            while ($barang = $barang_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $barang['id']; ?></td>
                <td><?= $barang['kategori_id']; ?></td>
                <td><?= $barang['nama_barang']; ?></td>
                <td><?= $barang['stok']; ?></td>
                <td><?= $barang['harga_beli']; ?></td>
                <td><?= $barang['harga_jual']; ?></td>
                <td><?= $barang['satuan']; ?></td>
                <td>
                    <form method="POST" action="barang.php" style="display:inline;">
                        <input type="hidden" name="id_barang" value="<?= $barang['id']; ?>">
                        <button type="submit" name="update" class="button btn-update">Update</button>
                        <button type="submit" name="delete" class="button btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
