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
        // Menambahkan pengembalian baru
        $id_restock = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah = $_POST['jumlah'];
        $keterangan = $_POST['keterangan'];

        $stmt = $config->prepare("INSERT INTO pengembalian (id_restock, id_supplier, tanggal_pengembalian, jumlah, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisds", $id_restock, $id_supplier, $tanggal_pengembalian, $jumlah, $keterangan);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate pengembalian
        $id_pengembalian = $_POST['id_pengembalian'];
        $id_restock = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah = $_POST['jumlah'];
        $keterangan = $_POST['keterangan'];

        $stmt = $config->prepare("UPDATE pengembalian SET id_restock = ?, id_supplier = ?, tanggal_pengembalian = ?, jumlah = ?, keterangan = ? WHERE id_pengembalian = ?");
        $stmt->bind_param("iisdsi", $id_restock, $id_supplier, $tanggal_pengembalian, $jumlah, $keterangan, $id_pengembalian);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus pengembalian
        $id_pengembalian = $_POST['id_pengembalian'];

        $stmt = $config->prepare("DELETE FROM pengembalian WHERE id_pengembalian = ?");
        $stmt->bind_param("i", $id_pengembalian);
        $stmt->execute();
        $stmt->close();
    }
}

// Menangani pencarian pengembalian
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $pengembalian_query = "SELECT * FROM pengembalian WHERE keterangan LIKE ? ORDER BY id_pengembalian DESC";
    $stmt = $config->prepare($pengembalian_query);
    $like_term = "%" . $search_term . "%";
    $stmt->bind_param("s", $like_term);
    $stmt->execute();
    $pengembalian_result = $stmt->get_result();
    $stmt->close();
} else {
    // Query untuk menampilkan semua pengembalian
    $pengembalian_query = "SELECT * FROM pengembalian ORDER BY id_pengembalian DESC";
    $pengembalian_result = $config->query($pengembalian_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Desain sama seperti sebelumnya */
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

input[type="text"],
input[type="date"],
input[type="number"] {
    width: 60%;
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
    border: 1px solid #ced4da;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

input[type="text"]::placeholder,
input[type="date"]::placeholder,
input[type="number"]::placeholder {
    color: #6c757d;
    opacity: 1;
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
    margin-bottom: 5px;
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

        /* Tambahkan desain CSS Anda di sini */
    </style>
    <script>
        function editPengembalian(id, restock, supplier, tanggal, jumlah, keterangan) {
            document.getElementById('id_pengembalian').value = id;
            document.getElementById('id_restock').value = restock;
            document.getElementById('id_supplier').value = supplier;
            document.getElementById('tanggal_pengembalian').value = tanggal;
            document.getElementById('jumlah').value = jumlah;
            document.getElementById('keterangan').value = keterangan;
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
        <h1>Data Pengembalian</h1>

        <form method="POST" action="pengembalian.php" style="display: flex; align-items: center;">
            <input type="hidden" id="id_pengembalian" name="id_pengembalian">
            <input type="text" id="id_restock" name="id_restock" placeholder="ID Restock" required>
            <input type="text" id="id_supplier" name="id_supplier" placeholder="ID Supplier" required>
            <input type="date" id="tanggal_pengembalian" name="tanggal_pengembalian" required>
            <input type="number" id="jumlah" name="jumlah" placeholder="Jumlah" required>
            <input type="text" id="keterangan" name="keterangan" placeholder="Keterangan" required>
            <button type="submit" id="submitBtn" name="submit" class="button btn-add">Insert</button>
            <button type="submit" id="updateBtn" name="update" class="button btn-update" style="display:none;">Update</button>
        </form>

        <!-- Search Form -->
        <form method="GET" action="pengembalian.php" class="search-form">
            <input type="text" name="search" placeholder="Search" value="<?= htmlspecialchars($search_term); ?>">
            <button type="submit" class="button btn-update">Search</button>
        </form>

        <table>
            <tr>
                <th>No</th>
                <th>ID Pengembalian</th>
                <th>ID Restock</th>
                <th>ID Supplier</th>
                <th>Tanggal Pengembalian</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            while ($pengembalian = $pengembalian_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $pengembalian['id_pengembalian']; ?></td>
                <td><?= $pengembalian['id_restock']; ?></td>
                <td><?= $pengembalian['id_supplier']; ?></td>
                <td><?= $pengembalian['tanggal_pengembalian']; ?></td>
                <td><?= $pengembalian['jumlah']; ?></td>
                <td><?= $pengembalian['keterangan']; ?></td>
                <td>
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
