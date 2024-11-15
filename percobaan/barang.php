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

// Fungsi untuk menangani aksi CRUD
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
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            padding: 15px;
            width: 100%;
        }

        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            height: 100vh;
            padding: 15px;
            position: fixed;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
        }

        .sidebar a:hover {
            background-color: #444;
        }

        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 15px;
        }

        h1 {
            font-size: 24px;
            color: #333;
        }

        .form-container {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
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
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            padding: 10px 15px;
            margin-top: 10px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .button {
            padding: 8px 15px;
            margin: 5px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
        }

        .btn-add {
            background-color: #4CAF50;
            color: white;
        }

        .btn-update {
            background-color: #007bff;
            color: white;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <?php include('sidebar.php'); ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Data Barang</h1>


        <!-- Form untuk Menambahkan atau Mengupdate Barang -->
        <div class="form-container">
            <form method="POST" action="barang.php">
                <input type="hidden" name="id_barang" value="<?= htmlspecialchars($id_barang_update); ?>">
                <label for="nama_barang">Nama Barang:</label>
                <input type="text" id="nama_barang" name="nama_barang" value="<?= htmlspecialchars($nama_barang); ?>" required><br>

                <label for="id_kategori">Kategori:</label>
                <select name="id_kategori" id="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($kategori = $kategori_result->fetch_assoc()): ?>
                        <option value="<?= $kategori['id_kategori']; ?>" <?= $id_kategori == $kategori['id_kategori'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($kategori['nama_kategori']); ?>
                        </option>
                    <?php endwhile; ?>
                </select><br>

                <label for="harga_beli">Harga Beli:</label>
                <input type="number" id="harga_beli" name="harga_beli" value="<?= htmlspecialchars($harga_beli); ?>" required><br>

                <label for="harga_jual">Harga Jual:</label>
                <input type="number" id="harga_jual" name="harga_jual" value="<?= htmlspecialchars($harga_jual); ?>" required><br>

                <label for="satuan_barang">Satuan Barang:</label>
                <input type="text" id="satuan_barang" name="satuan_barang" value="<?= htmlspecialchars($satuan_barang); ?>" required><br>

                <label for="stok">Stok:</label>
                <input type="number" id="stok" name="stok" value="<?= htmlspecialchars($stok); ?>" required><br>

                <button type="submit" name="submit" class="button <?= $btn_text === 'Update Barang' ? 'btn-update' : 'btn-add'; ?>">
                    <?= htmlspecialchars($btn_text); ?>
                </button>
                <?php if ($btn_text === 'Update Barang'): ?>
                    <a href="barang.php" class="button">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Form pencarian -->
        <div style="text-align: right; margin-bottom: 15px;">
            <form method="GET" action="barang.php">
                <input type="text" name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit">Cari</button>
            </form>
        </div>

        <!-- Tabel Barang -->
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
                            <a href="barang.php?edit=<?= $row['id_barang']; ?>" class="button btn-update">Edit</a>
                            <form method="POST" action="barang.php" style="display:inline;">
                                <input type="hidden" name="id_barang" value="<?= $row['id_barang']; ?>">
                                <button type="submit" name="delete" class="button btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
