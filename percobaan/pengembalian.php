<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Function to validate specific stock for restock
function validateStockForRestock($config, $id_restock, $jumlah_pengembalian) {
    $stock_check = $config->prepare("SELECT jumlah FROM restock WHERE id_restock = ?");
    $stock_check->bind_param("s", $id_restock);
    $stock_check->execute();
    $stock_result = $stock_check->get_result();
    $stock_row = $stock_result->fetch_assoc();

    // Check if stock for selected restock is sufficient
    return $stock_row['jumlah'] >= $jumlah_pengembalian;
}

// Fetch restock data for dropdown
$restock_query = "SELECT r.*, s.nama_supplier 
                  FROM restock r 
                  JOIN supplier s ON r.id_supplier = s.id_supplier 
                  WHERE r.jumlah > 0";
$restock_result = $config->query($restock_query);

// Handle CRUD actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tambah Data Pengembalian
    if (isset($_POST['submit'])) {
        $id_restock = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $nama_barang = $_POST['nama_barang'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah = $_POST['jumlah'];
        
        // Validasi keterangan dengan ketat
        $keterangan = trim($_POST['keterangan']);
        if (strlen($keterangan) === 0) {
            $_SESSION['error'] = "Keterangan harus diisi dengan deskripsi yang valid";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        $total_biaya_pengembalian = $_POST['total_biaya_pengembalian'];

        // Generate ID Pengembalian
        $query = "SELECT MAX(SUBSTRING(id_pengembalian, 3)) as max_id FROM pengembalian";
        $result = $config->query($query);
        $row = $result->fetch_assoc();
        $next_id = str_pad((int)$row['max_id'] + 1, 3, '0', STR_PAD_LEFT);
        $id_pengembalian = 'PG' . $next_id;

        // Start transaction
        $config->begin_transaction();

        try {
            // Validate stock for restock
            if (!validateStockForRestock($config, $id_restock, $jumlah)) {
                throw new Exception("Stok untuk restock {$id_restock} tidak mencukupi untuk pengembalian");
            }

            // Insert into pengembalian table
            $stmt = $config->prepare("INSERT INTO pengembalian 
                (id_pengembalian, id_restock, id_supplier, nama_barang, tanggal_pengembalian, jumlah, keterangan, total_biaya_pengembalian) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssississ", $id_pengembalian, $id_restock, $id_supplier, $nama_barang, 
                            $tanggal_pengembalian, $jumlah, $keterangan, $total_biaya_pengembalian);
            $stmt->execute();

            // Update stock for specific restock
            $stmt = $config->prepare("UPDATE restock SET jumlah = jumlah - ? WHERE id_restock = ?");
            $stmt->bind_param("is", $jumlah, $id_restock);
            $stmt->execute();

            $config->commit();
            $_SESSION['success'] = "Berhasil menambahkan data pengembalian";
        } catch (Exception $e) {
            $config->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Update Data Pengembalian 
    elseif (isset($_POST['update'])) {
        // Ambil data dari form
        $id_pengembalian = $_POST['id_pengembalian'];
        $id_restock_baru = $_POST['id_restock'];
        $id_supplier = $_POST['id_supplier'];
        $nama_barang = $_POST['nama_barang'];
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
        $jumlah_baru = $_POST['jumlah'];
        
        // Validasi keterangan dengan ketat
        $keterangan = trim($_POST['keterangan']);
        if ($keterangan === '') {
            $_SESSION['error'] = "Keterangan harus diisi dengan deskripsi yang valid";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        $total_biaya_pengembalian = $_POST['total_biaya_pengembalian'];

        // Fetch old return details - SEBELUM TRANSACTION
        $stmt = $config->prepare("SELECT id_restock, jumlah FROM pengembalian WHERE id_pengembalian = ?");
        $stmt->bind_param("s", $id_pengembalian);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_data = $result->fetch_assoc();
        $id_restock_lama = $old_data['id_restock'];
        $jumlah_lama = $old_data['jumlah'];

        // Start transaction
        $config->begin_transaction();

        try {
            // Validate stock for new restock considering the difference
            $selisih_jumlah = $jumlah_baru - $jumlah_lama;
            if (!validateStockForRestock($config, $id_restock_baru, abs($selisih_jumlah))) {
                throw new Exception("Stok untuk restock {$id_restock_baru} tidak mencukupi untuk update");
            }

            // Update return data
            $stmt = $config->prepare("UPDATE pengembalian SET 
                id_restock = ?, 
                id_supplier = ?, 
                nama_barang = ?, 
                tanggal_pengembalian = ?, 
                jumlah = ?, 
                keterangan = ?, 
                total_biaya_pengembalian = ? 
                WHERE id_pengembalian = ?");
            $stmt->bind_param("sississs", 
                $id_restock_baru, 
                $id_supplier, 
                $nama_barang, 
                $tanggal_pengembalian, 
                $jumlah_baru, 
                $keterangan,  
                $total_biaya_pengembalian, 
                $id_pengembalian
            );
            $stmt->execute();

            // Return stock to old restock
            $stmt = $config->prepare("UPDATE restock SET jumlah = jumlah + ? WHERE id_restock = ?");
            $stmt->bind_param("is", $jumlah_lama, $id_restock_lama);
            $stmt->execute();

            // Reduce stock from new restock
            $stmt = $config->prepare("UPDATE restock SET jumlah = jumlah - ? WHERE id_restock = ?");
            $stmt->bind_param("is", $jumlah_baru, $id_restock_baru);
            $stmt->execute();

            $config->commit();
            $_SESSION['success'] = "Berhasil memperbarui data pengembalian";
        } catch (Exception $e) {
            $config->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Hapus Data Pengembalian
    elseif (isset($_POST['delete'])) {
        $id_pengembalian = $_POST['id_pengembalian_data'];
        
        // Fetch return data before deletion
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
            // Delete from pengembalian table
            $stmt = $config->prepare("DELETE FROM pengembalian WHERE id_pengembalian = ?");
            $stmt->bind_param("s", $id_pengembalian);
            $stmt->execute();

            // Return stock to restock
            $stmt = $config->prepare("UPDATE restock SET jumlah = jumlah + ? WHERE id_restock = ?");
            $stmt->bind_param("is", $jumlah, $id_restock);
            $stmt->execute();

            $config->commit();
            $_SESSION['success'] = "Berhasil menghapus data pengembalian";
        } catch (Exception $e) {
            $config->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Proses pencarian
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
    <!-- <link href="style/pengembalian.css" rel="stylesheet" type="text/css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <style>/* Global Styling */
/* Global Styling */
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
    color: #333;
    background-color: #ffffff;
}

th:first-child {
    border-radius: 8px 0 0 8px; /* Melengkung hanya pada sisi kiri untuk ID */
}

td:first-child {
    border-bottom-left-radius: 8px;
}

th:last-child {
    border-radius: 0 8px 8px 0; /* Melengkung hanya pada sisi kanan untuk Aksi */
    border-right: none; 
}

td:last-child {
    border-bottom-right-radius: 8px;
    border-right: none;
}

/* Kolom tetap lurus tanpa melengkung */
th:nth-child(n+2):not(:last-child), td:nth-child(n+2):not(:last-child) {
    border-radius: 0; /* Kolom selain ID dan Aksi tetap lurus */
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

        <?php
        // Display success or error messages with auto-dismiss
        if (isset($_SESSION['success']) && 
            (!isset($_SESSION['show_message_until']) || time() <= $_SESSION['show_message_until'])) {
            echo "<div class='alert alert-success auto-dismiss'>" . htmlspecialchars($_SESSION['success']) . "</div>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error']) && 
            (!isset($_SESSION['show_message_until']) || time() <= $_SESSION['show_message_until'])) {
            echo "<div class='alert alert-danger auto-dismiss'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        // Clear the timer
        unset($_SESSION['show_message_until']);
        ?>

        <div class="top-actions">
            <button class="btn-add" onclick="openAddForm()">
                <i class="fas fa-plus"></i> 
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
                            <div style="display: flex; gap: 10px; justify-content: center;">
                                <!-- Tombol Hapus -->
                                <form method="POST" style="display:inline;" 
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    <input type="hidden" name="id_pengembalian_data" value="<?= $row['id_pengembalian']; ?>">
                                    <button type="submit" name="delete" 
                                        style="background: #dc3545; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                                        <i class="fa fa-trash" style="color: black; font-size: 20px;"></i>
                                    </button>
                                </form>

                                <!-- Tombol Edit -->
                                <button onclick="openUpdateForm(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                style="background: #ffc107; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                                    <i class="fa fa-pencil-alt" style="color: black; font-size: 20px;"></i>
                                </button>
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
            <h2>Tambah Data Pengembalian</h2>
            <form method="POST" action="">
                <div class="form-container">
                    <div class="form-group">
                        <label for="id_restock">ID Restock</label>
                        <select name="id_restock" id="id_restock" class="form-control" required onchange="updateSupplierAndPrice()">
                            <option value="">- Pilih ID Restock -</option>
                            <?php 
                            // Reset pointer to beginning of result set
                            $restock_result->data_seek(0);
                            while ($restock = $restock_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $restock['id_restock']; ?>" 
                                        data-supplier="<?= $restock['id_supplier']; ?>"
                                        data-supplier-name="<?= $restock['nama_supplier']; ?>"
                                        data-harga="<?= $restock['harga_beli']; ?>"
                                        data-stok="<?= $restock['jumlah']; ?>"
                                        data-nama-barang="<?= $restock['nama_barang']; ?>">
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
                               oninput="calculateTotal()" min="1">
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
                            // Reset pointer to beginning of result set
                            $restock_result->data_seek(0);
                            while ($restock = $restock_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $restock['id_restock']; ?>" 
                                        data-supplier="<?= $restock['id_supplier']; ?>"
                                        data-supplier-name="<?= $restock['nama_supplier']; ?>"
                                        data-harga="<?= $restock['harga_beli']; ?>"
                                        data-stok="<?= $restock['jumlah']; ?>"
                                        data-nama-barang="<?= $restock['nama_barang']; ?>">
                                    <?= $restock['id_restock']; ?> - <?= $restock['nama_barang']; ?> 
                                    (Stok: <?= $restock['jumlah']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_supplier_display">Supplier</label>
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
                               oninput="calculateTotalEdit()" required min="1">
                    </div>
                    <div class="form-group">
                    <label for="edit_keterangan">Keterangan</label>
                    <textarea 
                        name="keterangan" 
                        id="edit_keterangan" 
                        class="form-control" 
                        required 
                        placeholder="Masukkan keterangan pengembalian"
                        oninput="validateKeterangan(this)"
                    ></textarea>
                    <div id="keterangan-error" class="invalid-feedback" style="display: none;">
                        Keterangan tidak boleh kosong
                    </div>
                        </div>

                    <script>
                    function validateKeterangan(textarea) {
                        const errorDiv = document.getElementById('keterangan-error');
                        if (textarea.value.trim() === '') {
                            textarea.classList.add('is-invalid');
                            errorDiv.style.display = 'block';
                            return false;
                        } else {
                            textarea.classList.remove('is-invalid');
                            errorDiv.style.display = 'none';
                            return true;
                        }
                    }
                    </script>
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
            // Reset form
            document.getElementById('id_restock').selectedIndex = 0;
            document.getElementById('supplier_display').value = '';
            document.getElementById('id_supplier').value = '';
            document.getElementById('nama_barang').value = '';
            document.getElementById('tanggal_pengembalian').value = '';
            document.getElementById('jumlah').value = '';
            document.getElementById('keterangan').value = '';
            document.getElementById('total_biaya_pengembalian').value = '';

            document.getElementById('addModal').style.display = "block";
        }

        function closeAddForm() {
            document.getElementById('addModal').style.display = "none";
        }

        function updateSupplierAndPrice() {
            const select = document.getElementById('id_restock');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const supplierId = option.getAttribute('data-supplier');
                const supplierName = option.getAttribute('data-supplier-name');
                const hargaBeli = option.getAttribute('data-harga');
                const namaBarang = option.getAttribute('data-nama-barang');
                
                document.getElementById('id_supplier').value = supplierId;
                document.getElementById('supplier_display').value = supplierName;
                document.getElementById('nama_barang').value = namaBarang;
                
                currentHargaBeli = parseFloat(hargaBeli);
                calculateTotal();
            } else {
                // Reset fields if no option selected
                document.getElementById('id_supplier').value = '';
                document.getElementById('supplier_display').value = '';
                document.getElementById('nama_barang').value = '';
                document.getElementById('total_biaya_pengembalian').value = '';
            }
        }

        function calculateTotal() {
            const jumlah = document.getElementById('jumlah').value || 0;
            const totalBiaya = jumlah * currentHargaBeli;
            document.getElementById('total_biaya_pengembalian').value = totalBiaya;
        }

        function openUpdateForm(data) {
    // Jika data berupa string JSON, parse dulu
    data = typeof data === 'string' ? JSON.parse(data) : data;
    
    console.log("Data untuk edit:", data);

    // Isi form dengan data yang diterima
    document.getElementById('edit_id_pengembalian').value = data.id_pengembalian || '';
    document.getElementById('edit_nama_barang').value = data.nama_barang || '';
    document.getElementById('edit_tanggal_pengembalian').value = data.tanggal_pengembalian || '';
    document.getElementById('edit_jumlah').value = data.jumlah || '';

    // Pastikan keterangan diisi dengan string, bahkan jika nilainya '0'
    document.getElementById('edit_keterangan').value = data.keterangan !== null && data.keterangan !== undefined 
        ? String(data.keterangan).trim() 
        : '';

    document.getElementById('edit_total_biaya_pengembalian').value = data.total_biaya_pengembalian || '';

    // Set dropdown restock
    const select = document.getElementById('edit_id_restock');
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === data.id_restock) {
            select.selectedIndex = i;

            // Ambil data supplier dari atribut tambahan
            const option = select.options[i];
            const supplierId = option.getAttribute('data-supplier');
            const supplierName = option.getAttribute('data-supplier-name');
            const hargaBeli = option.getAttribute('data-harga');

            document.getElementById('edit_id_supplier').value = supplierId || '';
            document.getElementById('edit_supplier_display').value = supplierName || '';
            
            currentHargaBeliEdit = parseFloat(hargaBeli || 0);
            break;
        }
    }

    // Tampilkan modal edit
    document.getElementById('editModal').style.display = "block";
}

        function closeEditForm() {
            document.getElementById('editModal').style.display = "none";
        }

        function updateSupplierAndPriceEdit() {
            const select = document.getElementById('edit_id_restock');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const supplierId = option.getAttribute('data-supplier');
                const supplierName = option.getAttribute('data-supplier-name');
                const hargaBeli = option.getAttribute('data-harga');
                const namaBarang = option.getAttribute('data-nama-barang');
                
                document.getElementById('edit_id_supplier').value = supplierId;
                document.getElementById('edit_supplier_display').value = supplierName;
                
                currentHargaBeliEdit = parseFloat(hargaBeli);
                calculateTotalEdit();
            } else {
                // Reset fields if no option selected
                document.getElementById('edit_id_supplier').value = '';
                document.getElementById('edit_supplier_display').value = '';
                document.getElementById('edit_total_biaya_pengembalian').value = '';
            }
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