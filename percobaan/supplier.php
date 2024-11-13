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
        // Menambahkan supplier baru
        $nama_supplier = $_POST['nama_supplier'];
        $alamat = $_POST['alamat'];
        $telepon = $_POST['telepon'];

        $stmt = $config->prepare("INSERT INTO supplier (nama_supplier, alamat, telepon) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama_supplier, $alamat, $telepon);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate supplier
        $id_supplier = $_POST['id_supplier'];
        $nama_supplier = $_POST['nama_supplier'];
        $alamat = $_POST['alamat'];
        $telepon = $_POST['telepon'];

        $stmt = $config->prepare("UPDATE supplier SET nama_supplier = ?, alamat = ?, telepon = ? WHERE id_supplier = ?");
        $stmt->bind_param("sssi", $nama_supplier, $alamat, $telepon, $id_supplier);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus supplier
        $id_supplier = $_POST['id_supplier'];

        $stmt = $config->prepare("DELETE FROM supplier WHERE id_supplier = ?");
        $stmt->bind_param("i", $id_supplier);
        $stmt->execute();
        $stmt->close();
    }
}

// Query untuk menampilkan supplier
$supplier_query = "SELECT * FROM supplier ORDER BY id_supplier DESC";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $supplier_query = "SELECT * FROM supplier WHERE nama_supplier LIKE '%$search%' ORDER BY id_supplier DESC";
}
$supplier_result = $config->query($supplier_query);

// Jika ada data yang ingin diedit
$edit_supplier = null;
if (isset($_GET['id'])) {
    $id_supplier = $_GET['id'];
    $stmt = $config->prepare("SELECT * FROM supplier WHERE id_supplier = ?");
    $stmt->bind_param("i", $id_supplier);
    $stmt->execute();
    $edit_supplier = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-left: 250px; 
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        th {
            background-color: #343a40;
            color: #ffffff;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e9ecef;
        }
        .button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 2px;
        }
        .btn-add {
            background-color: #28a745;
            color: white;
        }
        .btn-update {
            background-color: #007bff;
            color: white;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .btn-update:hover {
            background-color: #0069d9;
        }
        .btn-add:hover {
            background-color: #218838;
        }
        .search-form {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
        .search-form input[type="text"] {
            width: 250px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }
        .search-form button {
            padding: 8px 15px;
            margin-left: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="container">
        <h1>Data Supplier</h1>

        <!-- Form untuk tambah atau update supplier -->
        <form method="POST" action="supplier.php">
            <div class="form-group">
                <label for="nama_supplier">Nama Supplier:</label>
                <input type="text" id="nama_supplier" name="nama_supplier" placeholder="Nama Supplier" required value="<?= $edit_supplier['nama_supplier'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="alamat">Alamat:</label>
                <input type="text" id="alamat" name="alamat" placeholder="Alamat" required value="<?= $edit_supplier['alamat'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="telepon">Telepon:</label>
                <input type="text" id="telepon" name="telepon" placeholder="Telepon" required value="<?= $edit_supplier['telepon'] ?? ''; ?>">
            </div>
            <input type="hidden" name="id_supplier" value="<?= $edit_supplier['id_supplier'] ?? ''; ?>">
            <button type="submit" name="<?= isset($edit_supplier) ? 'update' : 'submit'; ?>" class="button btn-add"><?= isset($edit_supplier) ? 'Update Data' : 'Tambah Supplier'; ?></button>
        </form>

        <!-- Form pencarian -->
        <form method="GET" action="supplier.php" class="search-form">
            <input type="text" name="search" placeholder="Cari Supplier" required>
            <button type="submit" class="button btn-update">Search</button>
        </form>

        <!-- Tabel Data Supplier -->
        <table>
            <tr>
                <th>No</th>
                <th>ID Supplier</th>
                <th>Nama Supplier</th>
                <th>Alamat</th>
                <th>Telepon</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            while ($supplier = $supplier_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $supplier['id_supplier']; ?></td>
                <td><?= $supplier['nama_supplier']; ?></td>
                <td><?= $supplier['alamat']; ?></td>
                <td><?= $supplier['telepon']; ?></td>
                <td>
                    <a href="supplier.php?id=<?= $supplier['id_supplier']; ?>">
                        <button class="button btn-update">Update</button>
                    </a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_supplier" value="<?= $supplier['id_supplier']; ?>">
                        <button type="submit" name="delete" class="button btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
