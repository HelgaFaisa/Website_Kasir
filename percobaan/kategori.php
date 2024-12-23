<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Fungsi untuk menangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Menambahkan kategori baru
    if (isset($_POST['submit'])) {
        $nama_kategori = trim($_POST['nama_kategori']);
        
        // Validasi input
        if (empty($nama_kategori)) {
            $_SESSION['error_message'] = "Nama kategori tidak boleh kosong!";
        } else {
            $tgl_input = date('Y-m-d H:i:s'); // Tanggal otomatis saat input
        
            // Ambil kode terakhir dari database
            $query = "SELECT kode_kategori FROM kategori ORDER BY kode_kategori DESC LIMIT 1";
            $result = $config->query($query);
            $row = $result->fetch_assoc();
        
            if ($row) {
                // Ekstrak angka dari kode terakhir
                $last_kode = intval(substr($row['kode_kategori'], 2));
                $new_kode = 'KT' . str_pad($last_kode + 1, 3, '0', STR_PAD_LEFT);
            } else {
                // Jika belum ada data, mulai dari KT001
                $new_kode = 'KT001';
            }
        
            // Masukkan data baru ke tabel
            $stmt = $config->prepare("INSERT INTO kategori (kode_kategori, nama_kategori, tgl_input) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $new_kode, $nama_kategori, $tgl_input);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Kategori berhasil ditambahkan!";
            } else {
                $_SESSION['error_message'] = "Gagal menambahkan kategori: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Update kategori
    elseif (isset($_POST['update'])) {
        $id_kategori = intval($_POST['id_kategori']);
        $nama_kategori = trim($_POST['nama_kategori']);
        
        if (empty($nama_kategori)) {
            $_SESSION['error_message'] = "Nama kategori tidak boleh kosong!";
        } else {
            $stmt = $config->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
            $stmt->bind_param("si", $nama_kategori, $id_kategori);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Kategori berhasil diupdate!";
            } else {
                $_SESSION['error_message'] = "Gagal mengupdate kategori: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Delete kategori
    elseif (isset($_POST['delete'])) {
        $id_kategori = intval($_POST['id_kategori']);
        
        $stmt = $config->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmt->bind_param("i", $id_kategori);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Kategori berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus kategori: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Menangani pencarian kategori
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $kategori_query = "SELECT * FROM kategori WHERE nama_kategori LIKE ? ORDER BY id_kategori DESC";
    $stmt = $config->prepare($kategori_query);
    $like_term = "%" . $search_term . "%";
    $stmt->bind_param("s", $like_term);
    $stmt->execute();
    $kategori_result = $stmt->get_result();
    $stmt->close();
} else {
    // Query untuk menampilkan semua kategori
    $kategori_query = "SELECT * FROM kategori ORDER BY id_kategori DESC";
    $kategori_result = $config->query($kategori_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- <link href="style/kategori.css" rel="stylesheet" type="text/css"> -->
    <style>
        .message-container {
            width: 100%;
            margin-bottom: 20px;
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
    input[type="text"] {
        width: 60%;
        padding: 10px;
        margin: 5px 0;
        border-radius: 5px;
        border: 1px solid #ced4da;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    input[type="text"]::placeholder {
        color: #6c757d;
        opacity: 1;
    }

    .table-responsive {
        overflow-x: auto;
        padding: 1px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        white-space: nowrap;
        border-radius: 12px; /* Sudut melengkung */
        overflow: hidden; /* Untuk menampilkan sudut melengkung */
    }
    th, td {
        padding: 12px 15px;
        text-align: center;
        border: 1px solid #ddd;
    }
    th {
        background-color: #800000;
        color: white;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    tr:hover {
        background-color: #f5f5f5;
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
        background-color: #800000;
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
        background-color: #800000;
    }
    .btn-add:hover {
        background-color: #982B1C
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
        background-color: #800000;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="container">
        <h1>Data Kategori</h1>


        <form method="POST" action="kategori.php" style="display: flex; align-items: center;">
            <input type="hidden" id="id_kategori" name="id_kategori">
            <input type="text" id="nama_kategori" name="nama_kategori" placeholder="Masukan kategori barang baru" required>
            <button type="submit" id="submitBtn" name="submit" class="button btn-add">
                <i class="fas fa-plus"></i>
            </button>
            <button type="submit" id="updateBtn" name="update" class="button btn-update" style="display:none;">
                <i class="fas fa-pen"></i>
            </button>
        </form>

        <!-- Search Form -->
        <form method="GET" action="kategori.php" class="search-form">
            <input type="text" name="search" placeholder="Search" value="<?= htmlspecialchars($search_term); ?>">
            <button type="submit" class="button btn-update">
                <i class="fas fa-search"></i>
            </button>
        </form>

         <!-- Message Container -->
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
            ?>
        </div>

        <table>
            <tr>
                <th>No</th>
                <th>Kode Kategori</th>
                <th>Nama Kategori</th>
                <th>Tanggal Input</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            while ($kategori = $kategori_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $kategori['kode_kategori']; ?></td>
                <td><?= $kategori['nama_kategori']; ?></td>
                <td><?= $kategori['tgl_input']; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori']; ?>">
                        <button type="submit" name="delete" 
                            onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');"
                            style="background: #dc3545; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                            <i class="fa fa-trash" style="color: white; font-size: 20px;"></i>
                        </button>
                    </form>
                    <button class="button btn-update" 
                        onclick="editKategori('<?= $kategori['id_kategori']; ?>', '<?= htmlspecialchars($kategori['nama_kategori']); ?>')"
                        style="background: #ffc107; border: none; border-radius: 5px; padding: 10px; cursor: pointer; transition: background 0.3s;">
                        <i class="fa fa-pencil-alt" style="color: black; font-size: 20px;"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <script>
        function editKategori(id, nama) {
            document.getElementById('id_kategori').value = id;
            document.getElementById('nama_kategori').value = nama;
            document.getElementById('submitBtn').style.display = 'none';
            document.getElementById('updateBtn').style.display = 'inline';
        }

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