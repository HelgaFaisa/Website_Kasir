<?php 
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Function to generate Kode Supplier (SP001, SP002, ...)
function generateKodeSupplier($config) {
    $result = $config->query("SELECT MAX(id_supplier) AS max_id FROM supplier");
    $row = $result->fetch_assoc();
    $last_id = $row['max_id'] ?? 0;
    $new_id = str_pad($last_id + 1, 3, '0', STR_PAD_LEFT);
    return "SP" . $new_id;
}

// Fungsi untuk menangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Menambahkan supplier baru
        $kode_supplier = generateKodeSupplier($config); // Generate new kode_supplier
        $nama_supplier = $_POST['nama_supplier'];
        $alamat = $_POST['alamat'];
        $telepon = $_POST['telepon'];

        $stmt = $config->prepare("INSERT INTO supplier (kode_supplier, nama_supplier, alamat, telepon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $kode_supplier, $nama_supplier, $alamat, $telepon);
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
    <link href="style/supllier.css" rel="stylesheet" type="text/css">
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

        <!-- Form pencarian supplier -->
        <form method="GET" action="suppliercoba.php" class="search-form" id="supplierSearchForm">
            <input type="text" name="search" placeholder="Cari Supplier" id="supplierSearchInput" value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit" class="button btn-update">Search</button>
        </form>

        <!-- Tabel Data Supplier -->
        <table>
            <tr>
                <th>No</th>
                <th>Kode Supplier</th>
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
                <td><?= $supplier['kode_supplier']; ?></td>
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
