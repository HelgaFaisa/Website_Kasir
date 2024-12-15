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
        $nama_supplier = trim($_POST['nama_supplier']);
        $alamat = trim($_POST['alamat']);
        $telepon = trim($_POST['telepon']);

        // Validasi input
        $errors = [];
        if (empty($nama_supplier)) $errors[] = "Nama supplier tidak boleh kosong";
        if (empty($alamat)) $errors[] = "Alamat tidak boleh kosong";
        if (empty($telepon)) $errors[] = "Telepon tidak boleh kosong";

        if (empty($errors)) {
            $kode_supplier = generateKodeSupplier($config); // Generate new kode_supplier

            $stmt = $config->prepare("INSERT INTO supplier (kode_supplier, nama_supplier, alamat, telepon) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $kode_supplier, $nama_supplier, $alamat, $telepon);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Supplier berhasil ditambahkan!";
            } else {
                $_SESSION['error_message'] = "Gagal menambahkan supplier: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['form_errors'] = $errors;
        }
    } elseif (isset($_POST['update'])) {
        // Mengupdate supplier
        $id_supplier = $_POST['id_supplier'];
        $nama_supplier = trim($_POST['nama_supplier']);
        $alamat = trim($_POST['alamat']);
        $telepon = trim($_POST['telepon']);

        // Validasi input
        $errors = [];
        if (empty($nama_supplier)) $errors[] = "Nama supplier tidak boleh kosong";
        if (empty($alamat)) $errors[] = "Alamat tidak boleh kosong";
        if (empty($telepon)) $errors[] = "Telepon tidak boleh kosong";

        if (empty($errors)) {
            $stmt = $config->prepare("UPDATE supplier SET nama_supplier = ?, alamat = ?, telepon = ? WHERE id_supplier = ?");
            $stmt->bind_param("sssi", $nama_supplier, $alamat, $telepon, $id_supplier);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Supplier berhasil diupdate!";
            } else {
                $_SESSION['error_message'] = "Gagal mengupdate supplier: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['form_errors'] = $errors;
        }
    } elseif (isset($_POST['delete'])) {
        // Menghapus supplier
        $id_supplier = $_POST['id_supplier'];

        $stmt = $config->prepare("DELETE FROM supplier WHERE id_supplier = ?");
        $stmt->bind_param("i", $id_supplier);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Supplier berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus supplier: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Query untuk menampilkan supplier
$supplier_query = "SELECT * FROM supplier ORDER BY id_supplier DESC";
if (isset($_GET['search'])) {
    $search = $config->real_escape_string($_GET['search']);
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
    <!-- <link href="style/supplier.css" rel="stylesheet" type="text/css"> -->
    <style>
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

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        border-radius: 12px; /* Sudut melengkung pada tabel */
        overflow: hidden;    /* Agar sudut melengkung terlihat */
    }

    th, td {
        padding: 12px 15px;
        text-align: center;
        border: 1px solid #ddd;
        white-space: nowrap;
    }

    th {
        background-color: #800000;
        color: white;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color:  #f9f9f9;
    }

    tr:hover {
        background-color:  #f0f0f0;
    }

    .button {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin: 2px;
    }

    .btn-add {
        background-color: #800000;
        color: white;
    }

    .btn-update {
        background-color: #e5ff00;
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
        background-color: #982B1C;
    }

    .search-form {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 15px;
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
        background-color: #800000;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        font-weight: bold;
        margin-bottom: 5px;
        display: block;
    }

    .form-group input {
        width: 100%;
        padding: 8px;
        border-radius: 5px;
        border: 1px solid #ced4da;
    }
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
        
        /* Alert Styles */
        .message-container {
            position: relative;
            margin-bottom: 15px;
            z-index: 1000;
        }

        .alert {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid transparent;
            border-radius: 4px;
            opacity: 1;
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
            transform: translateY(-100%);
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <div class="container">

        <h1>Data Supplier</h1>
        <button id="openModal" class="button btn-add">
            <i class="fas fa-plus"></i>
        </button>
        <form method="GET" action="supplier.php" class="search-form" id="supplierSearchForm">
            <input type="text" name="search" placeholder="Cari Supplier" id="supplierSearchInput" value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit" class="button btn-update">
                <i class="fas fa-search"></i>
            </button>        
        </form>

        <div class="message-container">
            <?php
            // Display success messages
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success">' . 
                     htmlspecialchars($_SESSION['success_message']) . 
                     '</div>';
                unset($_SESSION['success_message']);
            }

            // Display error messages
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger">' . 
                     htmlspecialchars($_SESSION['error_message']) . 
                     '</div>';
                unset($_SESSION['error_message']);
            }

            // Display form validation errors
            if (isset($_SESSION['form_errors'])) {
                echo '<div class="alert alert-danger">';
                foreach ($_SESSION['form_errors'] as $error) {
                    echo htmlspecialchars($error) . "<br>";
                }
                echo '</div>';
                unset($_SESSION['form_errors']);
            }
            ?>
        </div>

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
                        style="background: #ffc107; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                        <i class="fa fa-pencil-alt" style="color: black; font-size: 20px;"></i>
                    </button>
                    <form method="POST" style="display: inline; margin: 0;">
                        <input type="hidden" name="id_supplier" value="<?= $supplier['id_supplier']; ?>">
                        <button type="submit" name="delete" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')" 
                            style="background: #dc3545; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
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
