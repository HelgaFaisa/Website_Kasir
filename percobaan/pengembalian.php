<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Handle CRUD actions for returns
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Add new return data
        $id_restock = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $nama_barang = $_POST['nama_barang'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah = $_POST['jumlah'];
        $keterangan = $_POST['keterangan'];
        $total_biaya_pengembalian = $_POST['total_biaya_pengembalian'];

        // Generate ID Pengembalian (PG001 format)
        $query = "SELECT MAX(SUBSTRING(id_pengembalian, 3)) as max_id FROM pengembalian";
        $result = $config->query($query);
        $row = $result->fetch_assoc();
        $next_id = str_pad((int)$row['max_id'] + 1, 3, '0', STR_PAD_LEFT);
        $id_pengembalian = 'PG' . $next_id;

        // Start transaction
        $config->begin_transaction();

        try {
            // Insert into pengembalian table
            $stmt = $config->prepare("INSERT INTO pengembalian 
                (id_pengembalian, id_restock, id_supplier, nama_barang, tanggal_pengembalian, jumlah, keterangan, total_biaya_pengembalian) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssississ", $id_pengembalian, $id_restock, $id_supplier, $nama_barang, 
                            $tanggal_pengembalian, $jumlah, $keterangan, $total_biaya_pengembalian);
            $stmt->execute();

            // Update stock in restock table
            $stmt = $config->prepare("UPDATE restock SET jumlah = jumlah - ? WHERE id_restock = ?");
            $stmt->bind_param("ii", $jumlah, $id_restock);
            $stmt->execute();

            $config->commit();
            $stmt->close();
        } catch (Exception $e) {
            $config->rollback();
            throw $e;
        }
    } elseif (isset($_POST['update'])) {
        // Update return data
        $id_pengembalian = $_POST['id_pengembalian'];
        $id_restock = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $nama_barang = $_POST['nama_barang'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah_baru = $_POST['jumlah'];
        $keterangan = $_POST['keterangan'];
        $total_biaya_pengembalian = $_POST['total_biaya_pengembalian'];

        // Get old quantity
        $stmt = $config->prepare("SELECT jumlah FROM pengembalian WHERE id_pengembalian = ?");
        $stmt->bind_param("s", $id_pengembalian);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $jumlah_lama = $row['jumlah'];

        // Start transaction
        $config->begin_transaction();

        try {
            // Update pengembalian table
            $stmt = $config->prepare("UPDATE pengembalian SET 
                id_restock = ?, id_supplier = ?, nama_barang = ?, tanggal_pengembalian = ?, 
                jumlah = ?, keterangan = ?, total_biaya_pengembalian = ? 
                WHERE id_pengembalian = ?");
            $stmt->bind_param("sississ", $id_restock, $id_supplier, $nama_barang, 
                            $tanggal_pengembalian, $jumlah_baru, $keterangan, 
                            $total_biaya_pengembalian, $id_pengembalian);
            $stmt->execute();

            // Update stock in restock table
            $selisih = $jumlah_baru - $jumlah_lama;
            $stmt = $config->prepare("UPDATE restock SET jumlah = jumlah - ? WHERE id_restock = ?");
            $stmt->bind_param("ii", $selisih, $id_restock);
            $stmt->execute();

            $config->commit();
            $stmt->close();
        } catch (Exception $e) {
            $config->rollback();
            throw $e;
        }
    } elseif (isset($_POST['delete'])) {
        // Delete return data
        $id_pengembalian = $_POST['id_pengembalian_data'];
        
        // Get return data before deletion
        $stmt = $config->prepare("SELECT id_restock, jumlah FROM pengembalian WHERE id_pengembalian = ?");
        $stmt->bind_param("s", $id_pengembalian);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $id_restock = $row['id_restock'];
        $jumlah = $row['jumlah'];

        // Start transaction
        $config->begin_transaction();

        try {
            // Delete from pengembalian
            $stmt = $config->prepare("DELETE FROM pengembalian WHERE id_pengembalian = ?");
            $stmt->bind_param("s", $id_pengembalian);
            $stmt->execute();

            // Restore stock in restock
            $stmt = $config->prepare("UPDATE restock SET jumlah = jumlah + ? WHERE id_restock = ?");
            $stmt->bind_param("ii", $jumlah, $id_restock);
            $stmt->execute();

            $config->commit();
            $stmt->close();
        } catch (Exception $e) {
            $config->rollback();
            throw $e;
        }
    }
}

// Get restock data for dropdown
$restock_query = "SELECT r.*, s.nama_supplier FROM restock r 
                  LEFT JOIN supplier s ON r.id_supplier = s.id_supplier";
$restock_result = $config->query($restock_query);

// Process search for returns
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $returns_query = "SELECT p.*, s.nama_supplier 
                     FROM pengembalian p 
                     LEFT JOIN supplier s ON p.id_supplier = s.id_supplier 
                     WHERE p.nama_barang LIKE ? 
                        OR s.nama_supplier LIKE ? 
                        OR p.id_pengembalian LIKE ?";
    $stmt = $config->prepare($returns_query);
    $search_like = "%$search%";
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
    $stmt->execute();
    $returns_result = $stmt->get_result();
} else {
    $returns_query = "SELECT p.*, s.nama_supplier 
                     FROM pengembalian p 
                     LEFT JOIN supplier s ON p.id_supplier = s.id_supplier";
    $returns_result = $config->query($returns_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang - Toko Baju</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    border-collapse: collapse;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

th {
    background-color: #800000;
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 500;
}

td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background-color: #f8f9fa;
}

/* Button Actions */
.btn-update, .btn-delete {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-update {
    background-color: #ffc107;
    color: #000;
    margin-right: 5px;
}

.btn-delete {
    background-color: #dc3545;
    color: white;
}

.btn-update:hover, .btn-delete:hover {
    transform: translateY(-2px);
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

textarea.form-control {
    min-height: 100px;
    resize: vertical;
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

    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <div class="main-content">
        <h1>
            <i class="fas fa-undo"></i>
            Data Pengembalian Barang
        </h1>

        <div class="top-actions">
            <button class="btn-add" onclick="openAddForm()">
                <i class="fas fa-plus"></i> Tambah Data Pengembalian
            </button>

            <div class="search-bar">
                <form method="GET" action="" id="searchForm">
                    <input type="text" name="search" id="searchInput" 
                           placeholder="Cari ID pengembalian, nama barang, atau supplier..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID Pengembalian</th>
                    <th>ID Restock</th>
                    <th>Supplier</th>
                    <th>Nama Barang</th>
                    <th>Tanggal Pengembalian</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
                    <th>Total Biaya Pengembalian</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $returns_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id_pengembalian']; ?></td>
                        <td><?= $row['id_restock']; ?></td>
                        <td><?= $row['nama_supplier']; ?></td>
                        <td><?= $row['nama_barang']; ?></td>
                        <td><?= $row['tanggal_pengembalian']; ?></td>
                        <td><?= $row['jumlah']; ?></td>
                        <td><?= $row['keterangan']; ?></td>
                        <td>Rp <?= number_format($row['total_biaya_pengembalian'], 0, ',', '.'); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" 
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                <input type="hidden" name="id_pengembalian_data" value="<?= $row['id_pengembalian']; ?>">
                                <button type="submit" name="delete" class="btn-delete">Hapus</button>
                            </form>
                            <button onclick="openUpdateForm(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                    class="btn-update">Edit</button>
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
            <h2>Tambah Data Pengembalian</h2>
            <form method="POST" action="">
                <div class="form-container">
                    <div class="form-group">
                        <label for="id_restock">ID Restock</label>
                        <select name="id_restock" id="id_restock" class="form-control" required 
                                onchange="updateSupplierAndPrice()">
                            <option value="">- Pilih ID Restock -</option>
                            <?php 
                            $restock_result->data_seek(0);
                            while ($restock = $restock_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $restock['id_restock']; ?>" 
                                        data-supplier="<?= $restock['id_supplier']; ?>"
                                        data-harga="<?= $restock['harga_beli']; ?>"
                                        data-stok="<?= $restock['jumlah']; ?>">
                                    <?= $restock['id_restock']; ?> - <?= $restock['nama_barang']; ?> 
                                    (Stok: <?= $restock['jumlah']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_supplier">Supplier</label>
                        <input type="text" id="supplier_display" class="form-control" readonly>
                        <input type="hidden" name="id_supplier" id="id_supplier">
                    </div>
                    <div class="form-group">
                        <label for="nama_barang">Nama Barang</label>
                        <input type="text" name="nama_barang" id="nama_barang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_pengembalian">Tanggal Pengembalian</label>
                        <input type="date" name="tanggal_pengembalian" id="tanggal_pengembalian" 
                               class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="jumlah">Jumlah</label>
                        <input type="number" name="jumlah" id="jumlah" class="form-control" required 
                               oninput="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="total_biaya_pengembalian">Total Biaya Pengembalian</label>
                        <input type="number" name="total_biaya_pengembalian" id="total_biaya_pengembalian" 
                               class="form-control" readonly>
                    </div>
                    <button type="submit" name="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Data -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditForm()">&times;</span>
            <h2>Edit Data Pengembalian</h2>
            <form method="POST" action="">
                <div class="form-container">
                    <input type="hidden" name="id_pengembalian" id="edit_id_pengembalian">
                    <div class="form-group">
                        <label for="edit_id_restock">ID Restock</label>
                        <select name="id_restock" id="edit_id_restock" class="form-control" required 
                                onchange="updateSupplierAndPriceEdit()">
                            <option value="">- Pilih ID Restock -</option>
                            <?php 
                            $restock_result->data_seek(0);
                            while ($restock = $restock_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $restock['id_restock']; ?>" 
                                        data-supplier="<?= $restock['id_supplier']; ?>"
                                        data-harga="<?= $restock['harga_beli']; ?>"
                                        data-stok="<?= $restock['jumlah']; ?>">
                                    <?= $restock['id_restock']; ?> - <?= $restock['nama_barang']; ?> 
                                    (Stok: <?= $restock['jumlah']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_id_supplier">Supplier</label>
                        <input type="text" id="edit_supplier_display" class="form-control" readonly>
                        <input type="hidden" name="id_supplier" id="edit_id_supplier">
                    </div>
                    <div class="form-group">
                        <label for="edit_nama_barang">Nama Barang</label>
                        <input type="text" name="nama_barang" id="edit_nama_barang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_tanggal_pengembalian">Tanggal Pengembalian</label>
                        <input type="date" name="tanggal_pengembalian" id="edit_tanggal_pengembalian" 
                               class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_jumlah">Jumlah</label>
                        <input type="number" name="jumlah" id="edit_jumlah" class="form-control" 
                               oninput="calculateTotalEdit()" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_keterangan">Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_total_biaya_pengembalian">Total Biaya Pengembalian</label>
                        <input type="number" name="total_biaya_pengembalian" id="edit_total_biaya_pengembalian" 
                               class="form-control" readonly>
                    </div>
                    <button type="submit" name="update" class="btn-submit">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentHargaBeli = 0;
        let currentHargaBeliEdit = 0;

        function openAddForm() {
            document.getElementById('addModal').style.display = "block";
        }

        function closeAddForm() {
            document.getElementById('addModal').style.display = "none";
        }

        function updateSupplierAndPrice() {
            const select = document.getElementById('id_restock');
            const option = select.options[select.selectedIndex];
            const supplierId = option.getAttribute('data-supplier');
            const hargaBeli = option.getAttribute('data-harga');
            
            document.getElementById('id_supplier').value = supplierId;
            document.getElementById('supplier_display').value = option.text.split(' - ')[0];
            currentHargaBeli = parseFloat(hargaBeli);
            calculateTotal();
        }

        function calculateTotal() {
            const jumlah = document.getElementById('jumlah').value || 0;
            const totalBiaya = jumlah * currentHargaBeli;
            document.getElementById('total_biaya_pengembalian').value = totalBiaya;
        }

        function openUpdateForm(data) {
            data = typeof data === 'string' ? JSON.parse(data) : data;
            document.getElementById('edit_id_pengembalian').value = data.id_pengembalian;
            document.getElementById('edit_id_restock').value = data.id_restock;
            document.getElementById('edit_id_supplier').value = data.id_supplier;
            document.getElementById('edit_supplier_display').value = data.nama_supplier;
            document.getElementById('edit_nama_barang').value = data.nama_barang;
            document.getElementById('edit_tanggal_pengembalian').value = data.tanggal_pengembalian;
            document.getElementById('edit_jumlah').value = data.jumlah;
            document.getElementById('edit_keterangan').value = data.keterangan;
            document.getElementById('edit_total_biaya_pengembalian').value = data.total_biaya_pengembalian;
            
            // Get harga_beli from selected restock option
            const select = document.getElementById('edit_id_restock');
            const option = select.options[select.selectedIndex];
            currentHargaBeliEdit = parseFloat(option.getAttribute('data-harga'));
            
            document.getElementById('editModal').style.display = "block";
        }

        function closeEditForm() {
            document.getElementById('editModal').style.display = "none";
        }

        function updateSupplierAndPriceEdit() {
            const select = document.getElementById('edit_id_restock');
            const option = select.options[select.selectedIndex];
            const supplierId = option.getAttribute('data-supplier');
            const hargaBeli = option.getAttribute('data-harga');
            
            document.getElementById('edit_id_supplier').value = supplierId;
            document.getElementById('edit_supplier_display').value = option.text.split(' - ')[0];
            currentHargaBeliEdit = parseFloat(hargaBeli);
            calculateTotalEdit();
        }

        function calculateTotalEdit() {
            const jumlah = document.getElementById('edit_jumlah').value || 0;
            const totalBiaya = jumlah * currentHargaBeliEdit;
            document.getElementById('edit_total_biaya_pengembalian').value = totalBiaya;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) {
                closeAddForm();
            }
            if (event.target == editModal) {
                closeEditForm();
            }
        }
    </script>
</body>
</html>