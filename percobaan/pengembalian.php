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
    <meta charset="UTF-8
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang - Toko Baju</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f5;
        }

        .main-content {
            margin-left: 270px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
            color: #333;
        }

        .btn-add {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .btn-add:hover {
            background-color: #0056b3;
        }

        .search-bar {
            float: right;
            margin-bottom: 20px;
        }

        .search-bar input {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        /* Pop-up form styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 50px;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-container label {
            margin-top: 10px;
            display: block;
            font-weight: bold;
        }

        .form-container input,
        .form-container select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-container button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .form-container button:hover {
            background-color: #45a049;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #000000;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .btn-update, .btn-delete {
            padding: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-update {
            background-color: #ffc107;
            color: black;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <div class="main-content">
        <h1>Data Pengembalian Barang</h1>

        <!-- Search bar -->
        <div class="search-bar">
            <form method="POST" action="pengembalian.php">
                <input type="text" name="search_term" placeholder="Cari..." value="<?= htmlspecialchars($search_term); ?>">
                <button type="submit" name="search" class="btn btn-primary">Cari</button>
            </form>
        </div>

        <button class="btn-add" onclick="openModal()">Tambah Pengembalian</button>

        <!-- Modal Form -->
        <div id="formModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Form Pengembalian</h2>
                <form method="POST" action="pengembalian.php" class="form-container">
                    <input type="hidden" name="id_pengembalian" value="">
                    <div class="form-group">
                        <label for="id_restock">ID Restock:</label>
                        <input type="number" name="id_restock" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="id_barang">ID Barang:</label>
                        <select name="id_barang" class="form-control" required>
                            <?php while ($barang = $barang_result->fetch_assoc()): ?>
                                <option value="<?= $barang['id_barang'] ?>"><?= $barang['nama_barang'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_supplier">ID Supplier:</label>
                        <select name="id_supplier" class="form-control" required>
                            <?php while ($supplier = $supplier_result->fetch_assoc()): ?>
                                <option value="<?= $supplier['id_supplier'] ?>"><?= $supplier['nama_supplier'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_pengembalian">Tanggal Pengembalian:</label>
                        <input type="date" name="tanggal_pengembalian" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="jumlah">Jumlah:</label>
                        <input type="number" name="jumlah" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="kondisi_barang">Kondisi Barang:</label>
                        <input type="text" name="kondisi_barang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="alasan_pengembalian">Alasan Pengembalian:</label>
                        <input type="text" name="alasan_pengembalian" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="status_pengembalian">Status Pengembalian:</label>
                        <input type="text" name="status_pengembalian" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="total_biaya_pengembalian">Total Biaya Pengembalian:</label>
                        <input type="number" step="0.01" name="total_biaya_pengembalian" class="form-control" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                </form>
            </div>
        </div>

        <!-- Tabel Data Pengembalian -->
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
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['id_restock']); ?></td>
                            <td><?= htmlspecialchars($row['id_barang']); ?></td>
                            <td><?= htmlspecialchars($row['id_supplier']); ?></td>
                            <td><?= htmlspecialchars($row['tanggal_pengembalian']); ?></td>
                            <td><?= htmlspecialchars($row['jumlah']); ?></td>
                            <td><?= htmlspecialchars($row['kondisi_barang']); ?></td>
                            <td><?= htmlspecialchars($row['alasan_pengembalian']); ?></td>
                            <td><?= htmlspecialchars($row['status_pengembalian']); ?></td>
                            <td><?= number_format($row['total_biaya_pengembalian'], 2); ?></td>
                            <td>
                                <form method="POST" action="pengembalian.php" class="d-inline">
                                    <input type="hidden" name="id_pengembalian" value="<?= $row['id']; ?>">
                                    <button type="submit" name="update" class="btn btn-warning btn-sm">Update</button>
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pengembalian ini?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center">Tidak ada data pengembalian</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function openModal() {
            document.getElementById("formModal").style.display = "block";
        }
        function closeModal() {
            document.getElementById("formModal").style.display = "none";
            // Reset form inputs
            document.querySelector("form").reset();
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById("formModal")) {
                closeModal();
            }
        }
    </script>
</body>
</html>
 ⬤