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
    <link href="style/restock.css" rel="stylesheet" type="text/css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <i class="fas fa-plus"></i> Tambah Data Restock
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
