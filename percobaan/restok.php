<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Menangani aksi CRUD untuk restock
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Menambahkan data restock baru
        $id_supplier = $_POST['id_supplier'];
        $nama_barang = $_POST['nama_barang'];
        $tanggal_restock = $_POST['tanggal_restock'];
        $jumlah = $_POST['jumlah'];
        $harga_beli = $_POST['harga_beli'];
        $harga_total = $_POST['harga_total'];

        $stmt = $config->prepare("INSERT INTO restock 
            ( id_supplier, nama_barang, tanggal_restock, jumlah, harga_beli, harga_total) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $id_supplier, $nama_barang, $tanggal_restock, $jumlah, $harga_beli, $harga_total);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate data restock
        $id_restock = $_POST['id_restock'];
        $nama_barang = $_POST['nama_barang'];
        $id_supplier = $_POST['id_supplier'];
        $tanggal_restock = $_POST['tanggal_restock'];
        $jumlah = $_POST['jumlah'];
        $harga_beli = $_POST['harga_beli'];
        $harga_total = $_POST['harga_total'];

        $stmt = $config->prepare("UPDATE restock SET nama_barang = ?, id_supplier = ?, tanggal_restock = ?, jumlah = ?, harga_beli = ?, harga_total = ? WHERE id_restock = ?");
        $stmt->bind_param("ississ", $id_supplier, $nama_barang, $tanggal_restock, $jumlah, $harga_beli, $harga_total);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus data restock
        $id_restock_data = $_POST['id_restock_data'];

        $stmt = $config->prepare("DELETE FROM restock WHERE id_restock = ?");
        $stmt->bind_param("i", $id_restock_data);
        $stmt->execute();
        $stmt->close();
    }
}

// Proses pencarian
$search_term = '';
if (isset($_POST['search'])) {
    $search_term = $_POST['search_term'];
    $restock_query = "SELECT * FROM restock WHERE nama_barang LIKE ? OR id_supplier LIKE ? OR harga_beli LIKE ?";
    $stmt = $config->prepare($restock_query);
    $search_like = "%$search_term%";
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
    $stmt->execute();
    $restock_result = $stmt->get_result();
    $stmt->close();
} else {
    $restock_query = "SELECT * FROM restock";
    $restock_result = $config->query($restock_query);
}

$supplier_query = "SELECT * FROM supplier";
$supplier_result = $config->query($supplier_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restock Barang - Toko Baju</title>
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
        <h1>Data Restock Barang</h1>

        <!-- Search bar -->
        <div class="search-bar">
            <form method="POST" action="restok.php">
                <input type="text" name="search_term" placeholder="Cari..." value="<?= htmlspecialchars($search_term); ?>">
                <button type="submit" name="search" class="btn btn-primary">Search</button>
            </form>
        </div>

        <!-- Button to open the add modal -->
        <button class="btn-add" onclick="openAddForm()">Tambah Data Restock</button>

        <!-- Restock Data Table -->
        <table>
            <thead>
                <tr>
                    <th>ID Restock</th>
                    <th>Nama Barang</th>
                    <th>Supplier</th>
                    <th>Tanggal Restock</th>
                    <th>Jumlah</th>
                    <th>Harga Beli</th>
                    <th>Total Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $restock_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id_restock']; ?></td>
                        <td><?= $row['nama_barang']; ?></td>
                        <td><?= $row['id_supplier']; ?></td>
                        <td><?= $row['tanggal_restock']; ?></td>
                        <td><?= $row['jumlah']; ?></td>
                        <td><?= number_format($row['harga_beli'], 2); ?></td>
                        <td><?= number_format($row['harga_total'], 2); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_restock_data" value="<?= $row['id_restock']; ?>">
                                <button type="submit" name="delete" class="btn-delete">Hapus</button>
                            </form>
                            <button onclick="openUpdateForm(<?= $row['id_restock']; ?>)" class="btn-update">update</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddForm()">&times;</span>
            <h2>Tambah Data Restock</h2>
            <form method="POST" action="restok.php">

                <div class="form-container">
                    <label for="id_supplier">Supplier</label>
                    <select name="id_supplier" id="id_supplier" required>
                        <?php while ($supplier = $supplier_result->fetch_assoc()): ?>
                            <option value="<?= $supplier['id_supplier']; ?>"><?= $supplier['nama_supplier']; ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label for="nama_barang">Nama Barang</label>
                    <input type="varchar" name="nama_barang" id="nama_barang" required>

                    </select>

                    <label for="tanggal_restock">Tanggal Restock</label>
                    <input type="date" name="tanggal_restock" id="tanggal_restock" required>

                    <label for="jumlah">Jumlah</label>
                        <input type="number" name="jumlah" id="jumlah" oninput="calculateTotal()" required>

                        <label for="harga_beli">Harga Beli</label>
                        <input type="number" name="harga_beli" id="harga_beli" oninput="calculateTotal()" required>

                        <label for="harga_total">Harga Total</label>
                        <input type="number" name="harga_total" id="harga_total" readonly>

                    <button type="submit" name="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
         function calculateTotal() {
            const jumlah = document.getElementById('jumlah').value;
            const hargaBeli = document.getElementById('harga_beli').value;
            const hargaTotal = document.getElementById('harga_total');

            if (jumlah && hargaBeli) {
                hargaTotal.value = jumlah * hargaBeli;
            } else {
                hargaTotal.value = '';
            }
        }
        function openAddForm() {
            document.getElementById('addModal').style.display = "block";
        }

        function closeAddForm() {
            document.getElementById('addModal').style.display = "none";
        }
    </script>
</body>
</html>
