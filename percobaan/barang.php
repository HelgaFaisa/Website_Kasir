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
function generateKodeBarang($config) {
    $query = "SELECT kodebarang FROM barang ORDER BY id_barang DESC LIMIT 1";
    $result = $config->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastKode = $row['kodebarang'];
        $number = intval(substr($lastKode, 3)) + 1; // Ambil angka, tambah 1
        return "BGR" . str_pad($number, 3, "0", STR_PAD_LEFT);
    } else {
        return "BGR001"; // Kode pertama
    }
}

// Generate kode barang untuk diisi otomatis
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_kodebarang') {
    echo generateKodeBarang($config);
    exit;
}

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
    <link href="style/barang.css" rel="stylesheet" type="text/css">
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
     <!-- Tombol Edit -->
     <button onclick="openModal('Edit Barang', <?= htmlspecialchars(json_encode($barang)) ?>)" 
            style="background: #ffc107; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
            <i class="fa fa-pencil-alt" style="color: black; font-size: 20px;"></i>
        </button>

        <!-- Tombol Hapus -->
        <form method="post" style="margin: 0;">
            <input type="hidden" name="id_barang" value="<?= $barang['id_barang'] ?>">
            <button type="submit" name="delete" 
                style="background: #dc3545; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                <i class="fa fa-trash" style="color: black; font-size: 20px;"></i>
            </button>
        </form>

        <!-- Tombol Cetak Barcode -->
        <a href="print_barcode.php?id=<?= $barang['id_barang'] ?>" target="_blank"
           style="background: #007bff; border: none; border-radius: 5px; padding: 10px; text-decoration: none; display: inline-block;">
            <i class="fa fa-barcode" style="color: black; font-size: 20px;"></i>
        </a>
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
            <input type="text" name="kodebarang" id="kodebarang" readonly>
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
        <!-- <button onclick="openModal()">Tambah Barang</button> -->
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
function openModal() {
    // Panggil PHP untuk mendapatkan kode barang baru
    fetch('barang.php?action=get_kodebarang')
        .then(response => response.text())
        .then(kodebarang => {
            document.getElementById('kodebarang').value = kodebarang; // Isi otomatis input kodebarang
        })
        .catch(error => console.error('Error:', error));

    document.getElementById('modal').style.display = 'block'; // Tampilkan modal
}

function closeModal() {
    document.getElementById('modal').style.display = 'none'; // Sembunyikan modal
    document.getElementById('kodebarang').value = ''; // Reset kodebarang
}




</script>
</body>
</html>