<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

// Fungsi untuk menangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Menambahkan kategori baru
        $nama_kategori = $_POST['nama_kategori'];

        $stmt = $config->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
        $stmt->bind_param("s", $nama_kategori);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Mengupdate kategori
        $id_kategori = $_POST['id_kategori'];
        $nama_kategori = $_POST['nama_kategori'];

        $stmt = $config->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
        $stmt->bind_param("si", $nama_kategori, $id_kategori);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus kategori
        $id_kategori = $_POST['id_kategori'];

        $stmt = $config->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmt->bind_param("i", $id_kategori);
        $stmt->execute();
        $stmt->close();
    }
}

// Query untuk menampilkan kategori
$kategori_query = "SELECT * FROM kategori ORDER BY id_kategori DESC";
$kategori_result = $config->query($kategori_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Toko Baju</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styling untuk halaman kategori */
        .container {
            margin-left: 250px; /* Sesuaikan dengan lebar sidebar */
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="container">
        <h1>Data Kategori</h1>

        <form method="POST" action="kategori.php">
            <label for="nama_kategori">Nama Kategori:</label>
            <input type="text" id="nama_kategori" name="nama_kategori" required><br><br>

            <button type="submit" name="submit" class="button btn-add">Tambah Kategori</button>
        </form>

        <h2>Daftar Kategori</h2>

        <table>
            <tr>
                <th>No</th>
                <th>ID Kategori</th>
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
                <td><?= $kategori['id_kategori']; ?></td>
                <td><?= $kategori['nama_kategori']; ?></td>
                <td><?= $kategori['tanggal_input']; ?></td>
                <td>
                    <!-- Update and Delete buttons -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori']; ?>">
                        <button type="submit" name="delete" class="button btn-delete">Delete</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori']; ?>">
                        <input type="text" name="nama_kategori" value="<?= $kategori['nama_kategori']; ?>" required>
                        <button type="submit" name="update" class="button btn-update">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; 2024 Toko Baju
    </div>
</body>
</html>
