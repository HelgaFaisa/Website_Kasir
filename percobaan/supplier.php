<?php 
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Function to generate Kode Supplier (SP001, SP002, ...)
function generateKodeSupplier($config) {
    $result = $config->query("SELECT MAX(id_supplier) AS max_id FROM supplier");
    $row = $result->fetch_assoc();
    $last_id = $row['max_id'] ?? 0;
    $new_id = str_pad($last_id + 1, 3, '0', STR_PAD_LEFT);
    return "SP" . $new_id;
}

// Fungsi untuk menangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Menambahkan supplier baru
        $kode_supplier = generateKodeSupplier($config); // Generate new kode_supplier
        $nama_supplier = $_POST['nama_supplier'];
        $alamat = $_POST['alamat'];
        $telepon = $_POST['telepon'];

        $stmt = $config->prepare("INSERT INTO supplier (kode_supplier, nama_supplier, alamat, telepon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $kode_supplier, $nama_supplier, $alamat, $telepon);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate supplier
        $id_supplier = $_POST['id_supplier'];
        $nama_supplier = $_POST['nama_supplier'];
        $alamat = $_POST['alamat'];
        $telepon = $_POST['telepon'];

        $stmt = $config->prepare("UPDATE supplier SET nama_supplier = ?, alamat = ?, telepon = ? WHERE id_supplier = ?");
        $stmt->bind_param("sssi", $nama_supplier, $alamat, $telepon, $id_supplier);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus supplier
        $id_supplier = $_POST['id_supplier'];

        $stmt = $config->prepare("DELETE FROM supplier WHERE id_supplier = ?");
        $stmt->bind_param("i", $id_supplier);
        $stmt->execute();
        $stmt->close();
    }
}

// Query untuk menampilkan supplier
$supplier_query = "SELECT * FROM supplier ORDER BY id_supplier DESC";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $supplier_query = "SELECT * FROM supplier WHERE nama_supplier LIKE '%$search%' ORDER BY id_supplier DESC";
}
$supplier_result = $config->query($supplier_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style/supplier.css" rel="stylesheet" type="text/css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 10px;
            width: 30%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover, .close:focus {
            color: red;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <div class="container">
        <h1>Data Supplier</h1>
        <button id="openModal" class="button btn-add">
        <i class="fas fa-plus"></i> <!-- Ikon Plus dari Font Awesome -->
    </button>
        <form method="GET" action="supplier.php" class="search-form" id="supplierSearchForm">
            <input type="text" name="search" placeholder="Cari Supplier" id="supplierSearchInput" value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit" class="button btn-update">
            <i class="fas fa-search"></i> <!-- Ikon Search dari Font Awesome -->
        </button>        
    </form>

        <table>
            <tr>
                <th>No</th>
                <th>Kode Supplier</th>
                <th>Nama Supplier</th>
                <th>Alamat</th>
                <th>Telepon</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            while ($supplier = $supplier_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $supplier['kode_supplier']; ?></td>
                <td><?= $supplier['nama_supplier']; ?></td>
                <td><?= $supplier['alamat']; ?></td>
                <td><?= $supplier['telepon']; ?></td>
                <td>
                    <button 
                        class="edit-btn" 
                        data-id="<?= $supplier['id_supplier']; ?>" 
                        data-nama="<?= htmlspecialchars($supplier['nama_supplier']); ?>" 
                        data-alamat="<?= htmlspecialchars($supplier['alamat']); ?>" 
                        data-telepon="<?= htmlspecialchars($supplier['telepon']); ?>" 
                        style="background: #dc3545; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                        <i class="fa fa-pencil-alt" style="color: black; font-size: 20px;"></i>
                    </button>
                    <form method="POST" style="display: inline; margin: 0;">
                        <input type="hidden" name="id_supplier" value="<?= $supplier['id_supplier']; ?>">
                        <button type="submit" name="delete" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')" 
                            style="background: #ffc107; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                            <i class="fa fa-trash" style="color: black; font-size: 20px;"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Modal Tambah Supplier -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Tambah Supplier</h2>
            <form method="POST" action="supplier.php">
                <div class="form-group">
                    <label for="nama_supplier">Nama Supplier:</label>
                    <input type="text" id="nama_supplier" name="nama_supplier" placeholder="Nama Supplier" required>
                </div>
                <div class="form-group">
                    <label for="alamat">Alamat:</label>
                    <input type="text" id="alamat" name="alamat" placeholder="Alamat" required>
                </div>
                <div class="form-group">
                    <label for="telepon">Telepon:</label>
                    <input type="text" id="telepon" name="telepon" placeholder="Telepon" required>
                </div>
                <button type="submit" name="submit" class="button btn-add">Tambah Supplier</button>
            </form>
        </div>
    </div>

    <!-- Modal Edit Supplier -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Edit Supplier</h2>
            <form method="POST" action="supplier.php">
                <input type="hidden" id="edit_id_supplier" name="id_supplier">
                <div class="form-group">
                    <label for="edit_nama_supplier">Nama Supplier:</label>
                    <input type="text" id="edit_nama_supplier" name="nama_supplier" placeholder="Nama Supplier" required>
                </div>
                <div class="form-group">
                    <label for="edit_alamat">Alamat:</label>
                    <input type="text" id="edit_alamat" name="alamat" placeholder="Alamat" required>
                </div>
                <div class="form-group">
                    <label for="edit_telepon">Telepon:</label>
                    <input type="text" id="edit_telepon" name="telepon" placeholder="Telepon" required>
                </div>
                <button type="submit" name="update" class="button btn-add">Update Supplier</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal');
        const editModal = document.getElementById('editModal');
        const btn = document.getElementById('openModal');
        const closeEditModal = document.getElementById('closeEditModal');
        const span = document.getElementsByClassName('close')[0];
        const editBtns = document.querySelectorAll('.edit-btn');

        btn.onclick = function() {
            modal.style.display = "block";
        };

        span.onclick = function() {
            modal.style.display = "none";
        };

        closeEditModal.onclick = function() {
            editModal.style.display = "none";
        };

        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const alamat = this.getAttribute('data-alamat');
                const telepon = this.getAttribute('data-telepon');

                document.getElementById('edit_id_supplier').value = id;
                document.getElementById('edit_nama_supplier').value = nama;
                document.getElementById('edit_alamat').value = alamat;
                document.getElementById('edit_telepon').value = telepon;

                editModal.style.display = "block";
            });
        });

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            } else if (event.target == editModal) {
                editModal.style.display = "none";
            }
        };
    </script>
</body>
</html>
