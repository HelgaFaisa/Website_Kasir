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
        // Menambahkan restock baru
        $id_restock = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $nama_barang = $_POST['nama_barang'];
        $tanggal_restock = $_POST['tanggal_restock'];
        $harga_beli = $_POST['harga_beli'];
        $jumlah = $_POST['jumlah'];
        $harga_total = $harga_beli * $jumlah; // Hitung harga total

        $stmt = $config->prepare("INSERT INTO restock (id_restock, id_supplier, nama_barang, tanggal_restock, harga_beli, jumlah, harga_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdii", $id_restock, $id_supplier, $nama_barang, $tanggal_restock, $harga_beli, $jumlah, $harga_total);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate restock
        $id_restock = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $nama_barang = $_POST['nama_barang'];
        $tanggal_restock = $_POST['tanggal_restock'];
        $harga_beli = $_POST['harga_beli'];
        $jumlah = $_POST['jumlah'];
        $harga_total = $harga_beli * $jumlah; // Hitung harga total

        $stmt = $config->prepare("UPDATE restock SET id_supplier = ?, nama_barang = ?, tanggal_restock = ?, harga_beli = ?, jumlah = ?, harga_total = ? WHERE id_restock = ?");
        $stmt->bind_param("issdiii", $id_supplier, $nama_barang, $tanggal_restock, $harga_beli, $jumlah, $harga_total, $id_restock);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus restock
        $id_restock = $_POST['id_restock'];

        $stmt = $config->prepare("DELETE FROM restock WHERE id_restock = ?");
        $stmt->bind_param("i", $id_restock);
        $stmt->execute();
        $stmt->close();
    }
}

// Menangani pencarian restock
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $restock_query = "SELECT * FROM restock WHERE id_supplier LIKE ? ORDER BY id_restock DESC";
    $stmt = $config->prepare($restock_query);
    $like_term = "%" . $search_term . "%";
    $stmt->bind_param("s", $like_term);
    $stmt->execute();
    $restock_result = $stmt->get_result();
    $stmt->close();
} else {
    // Query untuk menampilkan semua restock
    $restock_query = "SELECT * FROM restock ORDER BY id_restock DESC";
    $restock_result = $config->query($restock_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restock - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS sama seperti sebelumnya */
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
        input[type="text"], input[type="date"], input[type="number"] {
            width: 60%;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ced4da;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
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
        .button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 2px;
        }
        .btn-add { background-color: #28a745; color: white; }
        .btn-update { background-color: #007bff; color: white; }
        .btn-delete { background-color: #dc3545; color: white; }
        .search-form input[type="text"] { width: 250px; padding: 8px; border-radius: 5px; border: 1px solid #ced4da; }
    </style>
    <script>
        function editRestock(id, supplier, barang, tanggal, jumlah, harga, total) {
            document.getElementById('id_restock').value = id;
            document.getElementById('id_supplier').value = supplier;
            document.getElementById('nama_barang').value = barang;
            document.getElementById('tanggal_restock').value = tanggal;
            document.getElementById('harga_beli').value = harga;
            document.getElementById('jumlah').value = jumlah;
            document.getElementById('harga_total').value = total;
            document.getElementById('submitBtn').style.display = 'none';
            document.getElementById('updateBtn').style.display = 'inline';
        }
    </script>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="container">
        <h1>Data Restock</h1>

        <form method="POST" action="restock.php" style="display: flex; align-items: center;">
            <input type="hidden" id="id_restock" name="id_restock">
            <input type="text" id="id_supplier" name="id_supplier" placeholder="ID Supplier" required>
            <input type="text" id="nama_barang" name="nama_barang" placeholder="Nama Barang" required>
            <input type="date" id="tanggal_restock" name="tanggal_restock" required>
            <input type="number" id="jumlah" name="jumlah" placeholder="Jumlah" required>
            <input type="number" id="harga_beli" name="harga_beli" placeholder="Harga Beli" required>
            <input type="number" id="harga_total" name="harga_total" placeholder="Harga Total" readonly>
            <button type="submit" id="submitBtn" name="submit" class="button btn-add">Insert</button>
            <button type="submit" id="updateBtn" name="update" class="button btn-update" style="display:none;">Update</button>
        </form>

        <!-- Search Form -->
        <form method="GET" action="restock.php" class="search-form">
            <input type="text" name="search" placeholder="Search" value="<?= htmlspecialchars($search_term); ?>">
            <button type="submit" class="button btn-update">Search</button>
        </form>

        <table>
            <tr>
                <th>No</th>
                <th>ID Supplier</th>
                <th>Nama Barang</th>
                <th>Tanggal Pembelian</th>
                <th>Harga Beli</th>
                <th>Jumlah</th>
                <th>Harga total</th>
            </tr>
            <?php
            $no = 1;
            while ($restock = $restock_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $restock['id_restock']; ?></td>
                <td><?= $restock['id_barang']; ?></td>
                <td><?= $restock['id_supplier']; ?></td>
                <td><?= $restock['tanggal_restock']; ?></td>
                <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_pengembalian" value="<?= $pengembalian['id_pengembalian']; ?>">
                        <button type="submit" name="delete" class="button btn-delete">Delete</button>
                    </form>
                    <button class="button btn-update" onclick="editPengembalian('<?= $pengembalian['id_pengembalian']; ?>', '<?= $pengembalian['id_restock']; ?>', '<?= $pengembalian['id_supplier']; ?>', '<?= $pengembalian['tanggal_pengembalian']; ?>', '<?= $pengembalian['jumlah']; ?>', '<?= $pengembalian['keterangan']; ?>')">Edit</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>