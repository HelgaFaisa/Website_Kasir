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
        // Sanitize and validate input
        $kodebarang = trim($_POST['kodebarang']);
        $nama_barang = trim($_POST['nama_barang']);
        $id_kategori = intval($_POST['id_kategori']);
        $harga_beli = floatval($_POST['harga_beli']);
        $harga_jual = floatval($_POST['harga_jual']);
        $stok = intval($_POST['stok']);
        $tgl_input = date('Y-m-d'); // Current date for new entries

        // Validate inputs
        $errors = [];
        if (empty($kodebarang)) $errors[] = "Kode barang tidak boleh kosong";
        if (empty($nama_barang)) $errors[] = "Nama barang tidak boleh kosong";
        if ($id_kategori <= 0) $errors[] = "Kategori harus dipilih";
        if ($harga_beli <= 0) $errors[] = "Harga beli harus lebih dari 0";
        if ($harga_jual <= 0) $errors[] = "Harga jual harus lebih dari 0";
        if ($stok < 0) $errors[] = "Stok tidak boleh negatif";

        // Check if there are any validation errors
        if (!empty($errors)) {
            // Store errors in session to display after redirect
            $_SESSION['form_errors'] = $errors;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        // Check if this is an update or new entry
        if (isset($_POST['id_barang']) && !empty($_POST['id_barang'])) {
            // Update existing barang
            $id_barang = $_POST['id_barang'];
            
            // Prepare update statement
            $stmt = $config->prepare("UPDATE barang 
                SET kodebarang = ?, 
                    nama_barang = ?, 
                    id_kategori = ?, 
                    harga_beli = ?, 
                    harga_jual = ?, 
                    stok = ? 
                WHERE id_barang = ?");
            
            // Bind parameters
            $stmt->bind_param("ssiiiis", 
                $kodebarang, 
                $nama_barang, 
                $id_kategori, 
                $harga_beli, 
                $harga_jual, 
                $stok, 
                $id_barang
            );
            
            // Execute update
            try {
                if ($stmt->execute()) {
                    // Set success message in session
                    $_SESSION['success_message'] = "Data barang berhasil diupdate!";
                } else {
                    // Set error message in session
                    $_SESSION['error_message'] = "Gagal mengupdate data: " . $stmt->error;
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
            
            $stmt->close();
        } else {
            // Insert new barang
            $stmt = $config->prepare("INSERT INTO barang (
                kodebarang, 
                nama_barang, 
                id_kategori, 
                harga_beli, 
                harga_jual, 
                stok, 
                tgl_input
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Bind parameters
            $stmt->bind_param("ssiiiis", 
                $kodebarang, 
                $nama_barang, 
                $id_kategori, 
                $harga_beli, 
                $harga_jual, 
                $stok, 
                $tgl_input
            );
            
            // Execute insert
            try {
                if ($stmt->execute()) {
                    // Set success message in session
                    $_SESSION['success_message'] = "Data barang berhasil ditambahkan!";
                } else {
                    // Set error message in session
                    $_SESSION['error_message'] = "Gagal menambahkan data: " . $stmt->error;
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
            
            $stmt->close();
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } 
    
    // Handle delete operation
    elseif (isset($_POST['delete'])) {
        $id_barang = intval($_POST['id_barang']);
        
        // Prepare delete statement
        $stmt = $config->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id_barang);
        
        try {
            if ($stmt->execute()) {
                // Set success message for deletion
                $_SESSION['success_message'] = "Data barang berhasil dihapus!";
            } else {
                // Set error message for deletion failure
                $_SESSION['error_message'] = "Gagal menghapus data: " . $stmt->error;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
        
        $stmt->close();
        
        // Redirect after delete
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
    <style>
    .message-container {
        margin-bottom: 20px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
        opacity: 1;
        transition: opacity 0.5s ease-out;
    }

    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .alert.fade-out {
        opacity: 0;
        height: 0;
        padding: 0;
        margin: 0;
        overflow: hidden;
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
                <i class="fas fa-plus"></i> 
            </button>
            
        </div>

        <!-- Form Pencarian -->
        <form class="search-bar" method="get">
            <input type="text" name="search" placeholder="Cari nama barang..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>

   <!-- Message Container -->
<div class="message-container">
    <?php
    // Display success messages
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success" id="success-alert">' . 
             htmlspecialchars($_SESSION['success_message']) . 
             '</div>';
        unset($_SESSION['success_message']);
    }

    // Display error messages
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger" id="error-alert">' . 
             htmlspecialchars($_SESSION['error_message']) . 
             '</div>';
        unset($_SESSION['error_message']);
    }

    // Display form validation errors
    if (isset($_SESSION['form_errors'])) {
        echo '<div class="alert alert-danger" id="errors-alert">';
        foreach ($_SESSION['form_errors'] as $error) {
            echo htmlspecialchars($error) . "<br>";
        }
        echo '</div>';
        unset($_SESSION['form_errors']);
    }
    ?>
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
    <button type="button" class="btn-cancel" onclick="closeModal()">
        <i class="fas fa-times"></i> Batal
    </button>
    <button type="submit" name="submit" class="btn-submit">
        <i class="fas fa-check"></i> <!-- Ikon Submit: Checkmark -->
        <?= $btn_text ?>
    </button>
</div>

        </form>
    </div>
</div>

<!-- Modal Edit (Updated) -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2><i class="fas fa-edit"></i> Edit Data Barang</h2>
        <form method="post" id="editForm">
            <input type="hidden" name="id_barang" id="edit_id_barang">
            <div class="form-group">
                <label for="edit_kodebarang">Kode Barang</label>
                <input type="text" name="kodebarang" id="edit_kodebarang" readonly>
            </div>
            <div class="form-group">
                <label for="edit_nama_barang">Nama Barang</label>
                <input type="text" name="nama_barang" id="edit_nama_barang" required>
            </div>
            <div class="form-group">
                <label for="edit_id_kategori">Kategori</label>
                <select name="id_kategori" id="edit_id_kategori" required>
                    <option value="">Pilih Kategori</option>
                    <?php 
                    // Reset the pointer for kategori result
                    $kategori_result->data_seek(0);
                    while ($kategori = $kategori_result->fetch_assoc()): ?>
                        <option value="<?= $kategori['id_kategori'] ?>"><?= htmlspecialchars($kategori['nama_kategori']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_harga_beli">Harga Beli</label>
                <input type="number" name="harga_beli" id="edit_harga_beli" required>
            </div>
            <div class="form-group">
                <label for="edit_harga_jual">Harga Jual</label>
                <input type="number" name="harga_jual" id="edit_harga_jual" required>
            </div>
            <div class="form-group">
                <label for="edit_stok">Stok</label>
                <input type="number" name="stok" id="edit_stok" required>
            </div>
            <div class="form-buttons">
    <button type="button" class="btn-cancel" onclick="closeEditModal()">
        <i class="fas fa-times"></i> Batal
    </button>
    <button type="submit" name="submit" class="btn-submit">
    <i class="fas fa-check"></i> Update Barang
</button>

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
    // Fungsi untuk membuka modal tambah barang
    function openModal(action, barang = null) {
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-action-text');
        const modalForm = document.querySelector('form');
        const modalIcon = document.getElementById('modal-icon');
        const submitButton = document.querySelector('button[name="submit"]');
        
        // Reset form dan modal
        modalForm.reset();
        submitButton.textContent = action === 'Tambah Barang' ? 'Tambah Barang' : 'Update Barang';

        if (action === 'Tambah Barang') {
            // Panggil PHP untuk mendapatkan kode barang baru
            fetch('barang.php?action=get_kodebarang')
                .then(response => response.text())
                .then(kodebarang => {
                    document.getElementById('kodebarang').value = kodebarang;
                })
                .catch(error => console.error('Error:', error));

            modalTitle.textContent = 'Tambah Barang';
            modalIcon.className = 'fas fa-plus';
            document.getElementById('id_barang').value = '';
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
        
        modal.style.display = 'block';
    }

    // Fungsi untuk membuka modal edit dengan parameter barang
    function openEditModal(barang) {
        // Populate form fields with barang data
        document.getElementById('id_barang').value = barang.id_barang;
        document.getElementById('kodebarang').value = barang.kodebarang;
        document.getElementById('nama_barang').value = barang.nama_barang;
        document.getElementById('id_kategori').value = barang.id_kategori;
        document.getElementById('harga_beli').value = barang.harga_beli;
        document.getElementById('harga_jual').value = barang.harga_jual;
        document.getElementById('stok').value = barang.stok;

        // Set modal title and icon
        const modalTitle = document.getElementById('modal-action-text');
        const modalIcon = document.getElementById('modal-icon');
        modalTitle.textContent = 'Edit Barang';
        modalIcon.className = 'fas fa-edit';

        // Set submit button text
        const submitButton = document.querySelector('button[name="submit"]');
        submitButton.textContent = 'Update Barang';

        // Show the modal
        document.getElementById('modal').style.display = 'block';
    }

    // Fungsi untuk menutup modal
    function closeModal() {
        document.getElementById('modal').style.display = 'none';
        
        // Reset form
        const modalForm = document.querySelector('form');
        modalForm.reset();
        
        // Clear any hidden input values
        document.getElementById('id_barang').value = '';
        document.getElementById('kodebarang').value = '';
    }

    // Setup event listeners for edit buttons
    function setupEditButtons() {
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const barangData = JSON.parse(this.getAttribute('data-barang'));
                openEditModal(barangData);
            });
        });
    }

    // Call setup function when page loads
    document.addEventListener('DOMContentLoaded', setupEditButtons);

    // Function to automatically dismiss alerts
    function dismissAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            // Add fade-out class after 5 seconds
            setTimeout(() => {
                alert.classList.add('fade-out');
                
                // Completely remove the alert after the fade-out animation
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }, 5000);
        });
    }

    // Call dismissAlerts when the page loads
    document.addEventListener('DOMContentLoaded', dismissAlerts);
</script>
</body>
</html>