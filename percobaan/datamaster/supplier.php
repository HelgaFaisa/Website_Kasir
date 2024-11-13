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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styling untuk halaman supplier */
        .container {
            margin-left: 250px; /* Sesuaikan dengan lebar sidebar */
            padding: 20px;
        }

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

        .btn-search {
            background-color: #ffa500;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="container">
        <h1>Data Supplier</h1>

        <!-- Form untuk tambah supplier -->
        <form method="POST" action="supplier.php">
            <label for="nama_supplier">Nama Supplier:</label>
            <input type="text" id="nama_supplier" name="nama_supplier" required><br><br>

            <label for="alamat">Alamat:</label>
            <input type="text" id="alamat" name="alamat" required><br><br>

            <label for="telepon">Telepon:</label>
            <input type="text" id="telepon" name="telepon" required><br><br>

            <button type="submit" name="submit" class="button btn-add">Tambah Supplier</button>
        </form>

        <h2>Daftar Supplier</h2>

        <!-- Form untuk pencarian supplier -->
        <form method="GET" action="supplier.php">
            <input type="text" name="search" placeholder="Cari Supplier..." required>
            <button type="submit" class="button btn-search">Cari</button>
        </form>

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
                    <!-- Update and Delete buttons -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_supplier" value="<?= $supplier['id_supplier']; ?>">
                        <button type="submit" name="delete" class="button btn-delete">Delete</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_supplier" value="<?= $supplier['id_supplier']; ?>">
                        <input type="text" name="nama_supplier" value="<?= $supplier['nama_supplier']; ?>" required>
                        <input type="text" name="alamat" value="<?= $supplier['alamat']; ?>" required>
                        <input type="text" name="telepon" value="<?= $supplier['telepon']; ?>" required>
                        <button type="submit" name="update" class="button btn-update">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; 2024 Toko Baju
    </div>
</body>
</html>
