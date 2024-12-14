<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Fungsi untuk generate ID Restock baru
function generateIdRestock($config) {
    $query = "SELECT id_restock FROM restock ORDER BY id_restock DESC LIMIT 1";
    $result = $config->query($query);
    $row = $result->fetch_assoc();

    $lastId = $row ? intval(substr($row['id_restock'], 2)) : 0;
    $nextId = $lastId + 1;
    return 'RS' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
}

// Insert data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $id_restock = generateIdRestock($config);
    $id_supplier = $_POST['id_supplier'];
    $nama_barang = $_POST['nama_barang'];
    $tanggal_restock = $_POST['tanggal_restock'];
    $jumlah = $_POST['jumlah'];
    $harga_beli = $_POST['harga_beli'];
    $harga_total = $jumlah * $harga_beli;

    $stmt = $config->prepare("INSERT INTO restock (id_restock, id_supplier, nama_barang, tanggal_restock, jumlah, harga_beli, harga_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdds", $id_restock, $id_supplier, $nama_barang, $tanggal_restock, $jumlah, $harga_beli, $harga_total);
    $stmt->execute();
    $stmt->close();
}

// Update data berdasarkan id_restock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id_restock = $_POST['id_restock'];
    $id_supplier = $_POST['id_supplier'];
    $nama_barang = $_POST['nama_barang'];
    $tanggal_restock = $_POST['tanggal_restock'];
    $jumlah = $_POST['jumlah'];
    $harga_beli = $_POST['harga_beli'];
    $harga_total = $jumlah * $harga_beli;

    $stmt = $config->prepare("UPDATE restock SET id_supplier = ?, nama_barang = ?, tanggal_restock = ?, jumlah = ?, harga_beli = ?, harga_total = ? WHERE id_restock = ?");
    $stmt->bind_param("sssddds", $id_supplier, $nama_barang, $tanggal_restock, $jumlah, $harga_beli, $harga_total, $id_restock);
    $stmt->execute();
    $stmt->close();
}

// Delete data berdasarkan id_restock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id_restock = $_POST['id_restock_data'];

    $stmt = $config->prepare("DELETE FROM restock WHERE id_restock = ?");
    $stmt->bind_param("s", $id_restock);
    $stmt->execute();
    $stmt->close();
}

// Ambil data supplier
$supplier_query = "SELECT * FROM supplier";
$supplier_result = $config->query($supplier_query);

// Proses pencarian restock
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $restock_query = "SELECT r.*, s.nama_supplier 
                      FROM restock r 
                      LEFT JOIN supplier s ON r.id_supplier = s.id_supplier 
                      WHERE r.nama_barang LIKE ? 
                         OR s.nama_supplier LIKE ? 
                         OR r.harga_beli LIKE ?";
    $stmt = $config->prepare($restock_query);
    $search_like = "%$search%";
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
    $stmt->execute();
    $restock_result = $stmt->get_result();
} else {
    $restock_query = "SELECT r.*, s.nama_supplier 
                      FROM restock r 
                      LEFT JOIN supplier s ON r.id_supplier = s.id_supplier";
    $restock_result = $config->query($restock_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restock Barang - Toko Baju</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- <link href="style/restock.css" rel="stylesheet" type="text/css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <style>
        /* Global Styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f6f9;
}

h1 {
    color: #2c3e50;
    font-size: 24px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #800000;
    padding-bottom: 10px;
}

h1 i {
    color: #800000;
    font-size: 28px;
}

/* Main Content */
.main-content {
    margin-left: 270px;
    padding: 25px;
    background-color: #ffffff;
    min-height: calc(100vh - 50px);
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

/* Top Actions Container */
.top-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* Search Bar */
.search-bar {
    display: flex;
    gap: 10px;
    max-width: 400px;
}

.search-bar input {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-bar input:focus {
    border-color: #800000;
    box-shadow: 0 0 0 2px rgba(128, 0, 0, 0.1);
    outline: none;
}

.search-bar button {
    background-color: #800000;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-bar button:hover {
    background-color: #990000;
}

/* Button Add */
.btn-add {
    background-color: #800000;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-add:hover {
    background-color: #990000;
    transform: translateY(-2px);
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: separate; /* Ubah dari collapse ke separate */
    border-spacing: 0; /* Hilangkan celah antar sel */
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    overflow: hidden; /* Pastikan radius berlaku */
}

th {
    background-color: #800000;
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 500;
}

th:first-child {
    border-top-left-radius: 8px;
}

th:last-child {
    border-top-right-radius: 8px;
}

td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}

tr:last-child td:first-child {
    border-bottom-left-radius: 8px;
}

tr:last-child td:last-child {
    border-bottom-right-radius: 8px;
}

tr:hover {
    background-color: #f8f9fa;
}

/* Button Actions */
.action-buttons {
    text-align: center;
}

.button-group {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.btn-update, .btn-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-update {
    background-color: #ffc107;
}

.btn-delete {
    background-color: #dc3545;
}

.btn-update i, .btn-delete i {
    color: black;
    font-size: 20px;
}

.btn-update:hover, .btn-delete:hover {
    transform: translateY(-2px);
    opacity: 0.8;
}

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow-y: auto;
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: #fff;
    margin: 40px auto;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    position: relative;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal h2 {
    color: #800000;
    font-size: 24px;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 2px solid #800000;
}

.close {
    position: absolute;
    right: 25px;
    top: 25px;
    font-size: 24px;
    font-weight: bold;
    color: #666;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: #800000;
}

/* Form Styling */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #800000;
    outline: none;
    box-shadow: 0 0 0 2px rgba(128, 0, 0, 0.1);
}

.btn-submit {
    background-color: #800000;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    font-weight: 500;
    margin-top: 20px;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    background-color: #990000;
    transform: translateY(-2px);
}

/* Modal Animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 15px;
    }

    .top-actions {
        flex-direction: column;
        gap: 15px;
    }

    .search-bar {
        max-width: 100%;
        order: 2;
    }

    .btn-add {
        width: 100%;
        justify-content: center;
        order: 1;
    }

    .modal-content {
        margin: 20px;
        padding: 20px;
    }
}

    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <div class="main-content">
        <h1>
            <i class="fas fa-box"></i>
            Data Restock Barang
        </h1>

        <div class="top-actions">
            <!-- Tombol untuk membuka modal tambah -->
            <button class="btn-add" onclick="openAddForm()">
                <i class="fas fa-plus"></i> 
            </button>

            <!-- Form Pencarian -->
            <div class="search-bar">
                <form method="GET" action="" id="searchForm">
                    <input type="text" name="search" id="searchInput" placeholder="Cari nama barang, supplier, atau harga..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel Data Restock -->
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
                        <td><?= $row['nama_supplier']; ?></td>
                        <td><?= $row['tanggal_restock']; ?></td>
                        <td><?= $row['jumlah']; ?></td>
                        <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                        <td>Rp <?= number_format($row['harga_total'], 0, ',', '.'); ?></td>
                        <td>
    <div style="display: flex; gap: 10px; justify-content: center;">
        <button onclick="openUpdateForm(<?= htmlspecialchars(json_encode($row)) ?>)" 
        style="background: #ffc107; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
            <i class="fa fa-pencil-alt" style="color: black; font-size: 20px;"></i>
        </button>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
            <input type="hidden" name="id_restock_data" value="<?= $row['id_restock']; ?>">
            <button type="submit" name="delete" 
                style="background: #dc3545; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                <i class="fa fa-trash" style="color: black; font-size: 20px;"></i>
            </button>
        </form>
    </div>
</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah Data -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddForm()">&times;</span>
            <h2>Tambah Data Restock</h2>
            <form method="POST" action="">
                <div class="form-container">
                    <div class="form-group">
                        <label for="id_supplier">Supplier</label>
                        <select name="id_supplier" id="id_supplier" class="form-control" required>
                            <option value="">- Pilih Supplier -</option>
                            <?php 
                            $supplier_result->data_seek(0);
                            while ($supplier = $supplier_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $supplier['id_supplier']; ?>"><?= $supplier['nama_supplier']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nama_barang">Nama Barang</label>
                        <input type="text" name="nama_barang" id="nama_barang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_restock">Tanggal Restock</label>
                        <input type="date" name="tanggal_restock" id="tanggal_restock" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="jumlah">Jumlah</label>
                        <input type="number" name="jumlah" id="jumlah" class="form-control" oninput="calculateTotal()" required>
                    </div>
                    <div class="form-group">
                        <label for="harga_beli">Harga Beli</label>
                        <input type="number" name="harga_beli" id="harga_beli" class="form-control" oninput="calculateTotal()" required>
                    </div>
                    <div class="form-group">
                        <label for="harga_total">Total Harga</label>
                        <input type="number" name="harga_total" id="harga_total" class="form-control" readonly>
                    </div>
                    <button type="submit" name="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditForm()">&times;</span>
            <h2>Edit Data Restock</h2>
            <form method="POST" action="">
                <div class="form-container">
                    <input type="hidden" name="id_restock" id="edit_id_restock">
                    <div class="form-group">
                        <label for="edit_id_supplier">Supplier</label>
                        <select name="id_supplier" id="edit_id_supplier" class="form-control" required>
                            <option value="">- Pilih Supplier -</option>
                            <?php 
                            $supplier_result->data_seek(0);
                            while ($supplier = $supplier_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $supplier['id_supplier']; ?>"><?= $supplier['nama_supplier']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_nama_barang">Nama Barang</label>
                        <input type="text" name="nama_barang" id="edit_nama_barang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_tanggal_restock">Tanggal Restock</label>
                        <input type="date" name="tanggal_restock" id="edit_tanggal_restock" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_jumlah">Jumlah</label>
                        <input type="number" name="jumlah" id="edit_jumlah" class="form-control" oninput="calculateTotalEdit()" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_harga_beli">Harga Beli</label>
                        <input type="number" name="harga_beli" id="edit_harga_beli" class="form-control" oninput="calculateTotalEdit()" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_harga_total">Total Harga</label>
                        <input type="number" name="harga_total" id="edit_harga_total" class="form-control" readonly>
                    </div>
                    <button type="submit" name="update" class="btn-submit">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddForm() { document.getElementById('addModal').style.display = "block"; }
        function closeAddForm() { document.getElementById('addModal').style.display = "none"; }
        function openUpdateForm(data) {
            data = typeof data === 'string' ? JSON.parse(data) : data;
            document.getElementById('edit_id_restock').value = data.id_restock;
            document.getElementById('edit_id_supplier').value = data.id_supplier;
            document.getElementById('edit_nama_barang').value = data.nama_barang;
            document.getElementById('edit_tanggal_restock').value = data.tanggal_restock;
            document.getElementById('edit_jumlah').value = data.jumlah;
            document.getElementById('edit_harga_beli').value = data.harga_beli;
            document.getElementById('edit_harga_total').value = data.harga_total;
            document.getElementById('editModal').style.display = "block";
        }
        function closeEditForm() { document.getElementById('editModal').style.display = "none"; }

        function calculateTotal() {
            const jumlah = document.getElementById('jumlah').value || 0;
            const hargaBeli = document.getElementById('harga_beli').value || 0;
            const totalHarga = jumlah * hargaBeli;
            document.getElementById('harga_total').value = totalHarga;
        }

        function calculateTotalEdit() {
            const jumlah = document.getElementById('edit_jumlah').value || 0;
            const hargaBeli = document.getElementById('edit_harga_beli').value || 0;
            const totalHarga = jumlah * hargaBeli;
            document.getElementById('edit_harga_total').value = totalHarga;
        }
    </script>
</body>
</html>