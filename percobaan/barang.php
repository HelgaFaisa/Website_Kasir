<?php 
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Inisialisasi variabel untuk form input
$nama_barang = $id_kategori = $harga_beli = $harga_jual = $satuan_barang = $stok = '';
$btn_text = 'Tambah Barang'; // Tombol default untuk tambah barang
$id_barang_update = null; // Variabel untuk ID barang yang sedang diupdate

// Menangani form input
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        if ($btn_text === 'Tambah Barang') {
            // Menambahkan barang baru
            $nama_barang = $_POST['nama_barang'];
            $id_kategori = $_POST['id_kategori'];
            $harga_beli = $_POST['harga_beli'];
            $harga_jual = $_POST['harga_jual'];
            $satuan_barang = $_POST['satuan_barang'];
            $stok = $_POST['stok'];
            $tgl_input = date('Y-m-d H:i:s');

            $stmt = $config->prepare("INSERT INTO barang (nama_barang, id_kategori, harga_beli, harga_jual, satuan_barang, stok, tgl_input, tgl_update) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siississ", $nama_barang, $id_kategori, $harga_beli, $harga_jual, $satuan_barang, $stok, $tgl_input, $tgl_input);
            $stmt->execute();
            $stmt->close();
        } elseif ($btn_text === 'Update Barang') {
            // Mengupdate barang
            $id_barang = $_POST['id_barang'];
            $nama_barang = $_POST['nama_barang'];
            $id_kategori = $_POST['id_kategori'];
            $harga_beli = $_POST['harga_beli'];
            $harga_jual = $_POST['harga_jual'];
            $satuan_barang = $_POST['satuan_barang'];
            $stok = $_POST['stok'];
            $tgl_update = date('Y-m-d H:i:s');

            $stmt = $config->prepare("UPDATE barang SET nama_barang = ?, id_kategori = ?, harga_beli = ?, harga_jual = ?, satuan_barang = ?, stok = ?, tgl_update = ? WHERE id_barang = ?");
            $stmt->bind_param("siissisi", $nama_barang, $id_kategori, $harga_beli, $harga_jual, $satuan_barang, $stok, $tgl_update, $id_barang);
            $stmt->execute();
            $stmt->close();

            echo "<script>alert('Data berhasil di-update');</script>";
        }

        // Reset form setelah menambah atau mengupdate barang
        $btn_text = 'Tambah Barang';
        $id_barang_update = null;
        $nama_barang = $id_kategori = $harga_beli = $harga_jual = $satuan_barang = $stok = '';
    } elseif (isset($_POST['delete'])) {
        // Menghapus barang
        $id_barang = $_POST['id_barang'];

        $stmt = $config->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id_barang);
        $stmt->execute();
        $stmt->close();
    }
}

// Menangani pemanggilan data barang untuk update
if (isset($_GET['edit'])) {
    $id_barang_update = $_GET['edit'];
    $barang_query = "SELECT * FROM barang WHERE id_barang = ?";
    $stmt = $config->prepare($barang_query);
    $stmt->bind_param("i", $id_barang_update);
    $stmt->execute();
    $result = $stmt->get_result();
    $barang = $result->fetch_assoc();

    if ($barang) {
        // Isi form dengan data barang yang akan diupdate
        $nama_barang = $barang['nama_barang'];
        $id_kategori = $barang['id_kategori'];
        $harga_beli = $barang['harga_beli'];
        $harga_jual = $barang['harga_jual'];
        $satuan_barang = $barang['satuan_barang'];
        $stok = $barang['stok'];

        // Ubah tombol menjadi "Update Barang"
        $btn_text = 'Update Barang';
    }
}

$kategori_query = "SELECT * FROM kategori";
$kategori_result = $config->query($kategori_query);

// Fungsi pencarian
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $barang_query_all = "SELECT barang.*, kategori.nama_kategori 
                         FROM barang 
                         JOIN kategori ON barang.id_kategori = kategori.id_kategori 
                         WHERE barang.nama_barang LIKE '%$search%'";
} else {
    $barang_query_all = "SELECT barang.*, kategori.nama_kategori 
                         FROM barang 
                         JOIN kategori ON barang.id_kategori = kategori.id_kategori";
}

$barang_result = $config->query($barang_query_all);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f5;
        }

        .main-content {
            margin-left: 270px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
            color: #333;
        }

        .btn-add {
            background-color: #800000;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .btn-add:hover {
            background-color: #982B1C;
        }

        .search-bar {
            float: right;
            margin-bottom: 20px;
            display: flex;
            gap: 8px; /* Memberikan jarak antara input dan button */
        }

        .search-bar input {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #800000; /* Warna maroon saat input difokuskan */
            box-shadow: 0 0 5px rgba(128, 0, 0, 0.2);
        }

        .search-bar button {
            padding: 8px 16px;
            background-color: #800000; /* Warna maroon untuk button */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .search-bar button:hover {
            background-color: #982B1C; /* Warna maroon yang lebih gelap saat hover */
        }
        /* Pop-up form styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 50px;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-container label {
            margin-top: 10px;
            display: block;
            font-weight: bold;
        }

        .form-container input,
        .form-container select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-container button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .form-container button:hover {
            background-color: #45a049;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #800000;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f0f0f0;
        }

        .btn-update, .btn-delete {
            padding: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-update {
            background-color: #ffc107;
            color: black;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>
    <div class="main-content">
        <h1>Data Barang</h1>

        <!-- Search bar -->
        <div class="search-bar">
            <form method="GET" action="barang.php">
                <input type="text" name="search" placeholder="Cari Barang..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <button class="btn-add" onclick="openModal()"><?= htmlspecialchars($btn_text); ?></button>

        <!-- Modal Form -->
        <div id="barangModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Form Barang</h2>
                <form method="POST" action="barang.php" class="form-container">
                    <input type="hidden" name="id_barang" value="<?= htmlspecialchars($id_barang_update); ?>">
                    <label for="nama_barang">Nama Barang:</label>
                    <input type="text" id="nama_barang" name="nama_barang" value="<?= htmlspecialchars($nama_barang); ?>" required>

                    <label for="id_kategori">Kategori:</label>
                    <select name="id_kategori" id="id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while ($kategori = $kategori_result->fetch_assoc()): ?>
                            <option value="<?= $kategori['id_kategori']; ?>" <?= $id_kategori == $kategori['id_kategori'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kategori['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="harga_beli">Harga Beli:</label>
                    <input type="number" id="harga_beli" name="harga_beli" value="<?= htmlspecialchars($harga_beli); ?>" required>

                    <label for="harga_jual">Harga Jual:</label>
                    <input type="number" id="harga_jual" name="harga_jual" value="<?= htmlspecialchars($harga_jual); ?>" required>

                    <label for="satuan_barang">Satuan Barang:</label>
                    <input type="text" id="satuan_barang" name="satuan_barang" value="<?= htmlspecialchars($satuan_barang); ?>" required>

                    <label for="stok">Stok:</label>
                    <input type="number" id="stok" name="stok" value="<?= htmlspecialchars($stok); ?>" required>

                    <button type="submit" name="submit"><?= htmlspecialchars($btn_text); ?></button>
                    <button type="button" onclick="closeModal()">Batal</button>
                </form>
            </div>
        </div>

        <!-- Tabel Data Barang -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Satuan</th>
                    <th>Stok</th>
                    <th>Tgl Input</th>
                    <th>Tgl Update</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $barang_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_barang']); ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                        <td><?= htmlspecialchars($row['harga_beli']); ?></td>
                        <td><?= htmlspecialchars($row['harga_jual']); ?></td>
                        <td><?= htmlspecialchars($row['satuan_barang']); ?></td>
                        <td><?= htmlspecialchars($row['stok']); ?></td>
                        <td><?= htmlspecialchars($row['tgl_input']); ?></td>
                        <td><?= htmlspecialchars($row['tgl_update']); ?></td>
                        <td>
                            <a href="barang.php?edit=<?= $row['id_barang']; ?>" class="btn-update">Edit</a>
                            <form method="POST" action="barang.php" style="display:inline;">
                                <input type="hidden" name="id_barang" value="<?= $row['id_barang']; ?>">
                                <button type="submit" name="delete" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        function openModal() {
            document.getElementById('barangModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('barangModal').style.display = 'none';
            window.location.href = 'barang.php'; // Refresh halaman agar form kosong saat modal ditutup
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('barangModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
