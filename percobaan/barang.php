<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}
require_once 'config.php';

// Tambahkan kode berikut
require_once __DIR__ . '/vendor/autoload.php';

// 5. Import namespace
use Picqer\Barcode\BarcodeGeneratorHTML;

// 6. Inisialisasi generator barcode
$generator = new BarcodeGeneratorHTML();

// Inisialisasi variabel untuk form input
$kodebarang = $nama_barang = $id_kategori = $harga_beli = $harga_jual = $stok = '';
$btn_text = 'Update Barang'; // Tombol default untuk tambah barang
$id_barang_update = null; // Variabel untuk ID barang yang sedang diupdate

// Menangani form input
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        $kodebarang = $_POST['kodebarang'];
        $nama_barang = $_POST['nama_barang'];
        $id_kategori = $_POST['id_kategori'];
        $harga_beli = $_POST['harga_beli'];
        $harga_jual = $_POST['harga_jual'];
        $stok = $_POST['stok'];
        $tgl_input = date('Y-m-d'); // Format sesuai tipe DATE

        if (isset($_POST['id_barang']) && !empty($_POST['id_barang'])) {
            // Update barang
            $id_barang = $_POST['id_barang'];
            $stmt = $config->prepare("UPDATE barang SET kodebarang = ?, nama_barang = ?, id_kategori = ?, 
                                    harga_beli = ?, harga_jual = ?, stok = ? WHERE id_barang = ?");
            $stmt->bind_param("ssiiiis", $kodebarang, $nama_barang, $id_kategori, $harga_beli, $harga_jual, $stok, $id_barang);
        } else {
            // Tambah barang baru
            $stmt = $config->prepare("INSERT INTO barang (kodebarang, nama_barang, id_kategori, harga_beli, 
                                    harga_jual, stok, tgl_input) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiis", $kodebarang, $nama_barang, $id_kategori, $harga_beli, $harga_jual, $stok, $tgl_input);
        }
        
        if ($stmt->execute()) {
            // Redirect untuk menghindari pengiriman ulang form
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        $id_barang = $_POST['id_barang'];
        $stmt = $config->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id_barang);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
// Query untuk kategori
$kategori_query = "SELECT * FROM kategori";
$kategori_result = $config->query($kategori_query);

// Fungsi pencarian
$search = $_GET['search'] ?? '';

// Jika pencarian kosong, arahkan ulang ke halaman tanpa parameter
if (isset($_GET['search']) && $search === '') {
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (!empty($search)) {
    $barang_query_all = $config->prepare("SELECT barang.*, kategori.nama_kategori 
                                         FROM barang 
                                         JOIN kategori ON barang.id_kategori = kategori.id_kategori 
                                         WHERE barang.nama_barang LIKE ?");
    $search_like = "%$search%";
    $barang_query_all->bind_param("s", $search_like);
    $barang_query_all->execute();
    $barang_result = $barang_query_all->get_result();
} else {
    $barang_query_all = "SELECT barang.*, kategori.nama_kategori 
                         FROM barang 
                         JOIN kategori ON barang.id_kategori = kategori.id_kategori";
    $barang_result = $config->query($barang_query_all);
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

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
    }

    /* Main Content */
    .main-content {
        margin-left: 270px;
        padding: 25px;
        background-color: #ffffff;
        min-height: calc(100vh - 50px);
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }

    /* Action Bar */
    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .button-group {
        display: flex;
        gap: 10px;
    }

    /* Buttons */
    .btn-add, .btn-submit {
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

    .btn-add:hover, .btn-submit:hover {
        background-color: #990000;
        transform: translateY(-2px);
    }

    /* Form Buttons */
    .form-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px; /* Jarak antar tombol */
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    /* Button Update */
    .btn-submit {
        padding: 12px 24px;
        background-color: #800000; /* Maroon */
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        background-color: #990000; /* Hover warna lebih terang */
        transform: translateY(-2px);
    }

    /* Button Cancel */
    .btn-cancel {
        padding: 12px 24px;
        background-color: #800000; /* Maroon */
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background-color: #990000; /* Hover warna lebih terang */
        transform: translateY(-2px);
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

    /* Tables */
    .table-responsive {
        overflow-x: auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
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

    .action-btns {
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    .action-btns button[name="edit"] {
        background-color: #ffc107;
        color: #000;
    }

    .action-btns button[name="delete"] {
        background-color: #dc3545;
        color: white;
    }

    .action-btns button:hover {
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

    /* Form Groups */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 700;
        font-size: 14px;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #800000;
        box-shadow: 0 0 0 2px rgba(128, 0, 0, 0.1);
        outline: none;
    }
</style>

</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <h1><i class="fas fa-box"></i> Data Barang</h1>

    <div class="action-bar">
        <div class="button-group">
            <button class="btn-add" onclick="openModal('Tambah Barang')">
                <i class="fas fa-plus"></i> Tambah Barang
            </button>
            
        </div>

        <!-- Form Pencarian -->
        <form class="search-bar" method="get">
            <input type="text" name="search" placeholder="Cari nama barang..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Barcode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Tanggal Input</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php if ($barang_result->num_rows > 0): ?>
                            <?php $no = 1; ?>
                            <?php while ($barang = $barang_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($barang['kodebarang']) ?></td>
                                    <td style="text-align: center;">
                                        <?php
                                        try {
                                            echo $generator->getBarcode($barang['kodebarang'], $generator::TYPE_CODE_128);
                                        } catch (Exception $e) {
                                            echo "Error generating barcode";
                                        }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                                    <td><?= htmlspecialchars($barang['nama_kategori']) ?></td>
                                    <td>Rp <?= number_format($barang['harga_beli'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($barang['harga_jual'], 0, ',', '.') ?></td>
                                    <td><?= $barang['stok'] ?></td>
                                    <td><?= $barang['tgl_input'] ?></td>
                                    <td>
                                    <div style="display: flex; gap: 10px; align-items: center;">
    <!-- Tombol Hapus -->
    <form method="post" style="margin: 0;">
        <input type="hidden" name="id_barang" value="<?= $barang['id_barang'] ?>">
        <button type="submit" name="delete" 
                style="background: #dc3545; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
            <i class="fa fa-trash" style="color: black; font-size: 20px;"></i>
        </button>
    </form>

    <!-- Tombol Edit -->
    <button onclick="openModal('Edit Barang', <?= htmlspecialchars(json_encode($barang)) ?>)" 
            style="background: #ffc107; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
        <i class="fa fa-pencil-alt" style="color: black; font-size: 20px;"></i>
    </button>
</div>



                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">Data barang tidak ditemukan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
            </table>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modal-title"><i id="modal-icon" class="fas"></i> <span id="modal-action-text">Tambah Barang</span></h2>
        <form method="post">
            <input type="hidden" name="id_barang" id="id_barang">
            <div class="form-group">
                <label for="kodebarang">Kode Barang</label>
                <input type="text" name="kodebarang" id="kodebarang" required>
            </div>
            <div class="form-group">
                <label for="nama_barang">Nama Barang</label>
                <input type="text" name="nama_barang" id="nama_barang" required>
            </div>
            <div class="form-group">
                <label for="id_kategori">Kategori</label>
                <select name="id_kategori" id="id_kategori" required>
                    <option value="">Pilih Kategori</option>
                    <?php while ($kategori = $kategori_result->fetch_assoc()): ?>
                        <option value="<?= $kategori['id_kategori'] ?>"><?= htmlspecialchars($kategori['nama_kategori']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="harga_beli">Harga Beli</label>
                <input type="number" name="harga_beli" id="harga_beli" required>
            </div>
            <div class="form-group">
                <label for="harga_jual">Harga Jual</label>
                <input type="number" name="harga_jual" id="harga_jual" required>
            </div>
            <div class="form-group">
                <label for="stok">Stok</label>
                <input type="number" name="stok" id="stok" required>
            </div>
            <div class="form-buttons">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" name="submit" class="btn-submit"><?= $btn_text ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Action Buttons -->
<div class="action-bar">
    <div class="button-group">
        <button class="btn-add" onclick="openModal('Tambah Barang')">
            <i class="fas fa-plus"></i> Tambah Barang
        </button>
    </div>
</div>


<script>
    // Fungsi untuk membuka modal
    function openModal(action, barang = null) {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modal-action-text');
    const modalForm = document.querySelector('form');
    const modalIcon = document.getElementById('modal-icon');
    
    if (action === 'Tambah Barang') {
        modalTitle.textContent = 'Tambah Barang';
        modalIcon.className = 'fas fa-plus';
        modalForm.reset();  // Reset form for adding new item
        document.getElementById('id_barang').value = '';  // Ensure the ID is cleared for a new item
    } else if (action === 'Edit Barang') {
        modalTitle.textContent = 'Edit Barang';
        modalIcon.className = 'fas fa-edit';
        // Pre-fill the form with item data
        document.getElementById('id_barang').value = barang.id_barang;
        document.getElementById('kodebarang').value = barang.kodebarang;
        document.getElementById('nama_barang').value = barang.nama_barang;
        document.getElementById('id_kategori').value = barang.id_kategori;
        document.getElementById('harga_beli').value = barang.harga_beli;
        document.getElementById('harga_jual').value = barang.harga_jual;
        document.getElementById('stok').value = barang.stok;
    }
    modal.style.display = 'block';  // Show modal
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';  // Hide modal
}


</script>
</body>
</html>