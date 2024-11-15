<?php
// Mulai session di awal file
session_start();

// Koneksi ke database
$config = mysqli_connect("localhost", "root", "", "tokobaju");

// Cek koneksi
if (!$config) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah user sudah login 
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

// Simpan aktivitas mengakses halaman log
$id_pengguna = $_SESSION['user']['id'];
$aktivitas = "Mengakses halaman log aktivitas";
$ip_address = $_SERVER['REMOTE_ADDR'];

// Menyimpan log aktivitas menggunakan prepared statement
$log_stmt = mysqli_prepare($config, "INSERT INTO log_aktivitas (id_pengguna, aktivitas, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("iss", $id_pengguna, $aktivitas, $ip_address);
$log_stmt->execute();

// Query untuk menampilkan log aktivitas berdasarkan id_pengguna yang sedang login
$query_log = "SELECT l.id_log, l.aktivitas, l.ip_address, l.created_at, u.email 
              FROM log_aktivitas l
              LEFT JOIN login u ON l.id_pengguna = u.id 
              WHERE l.id_pengguna = ?
              ORDER BY l.created_at DESC";

$stmt = mysqli_prepare($config, $query_log);
$stmt->bind_param("i", $id_pengguna);
$stmt->execute();
$result_log = $stmt->get_result();
?>

<!-- Content area - tidak perlu tag HTML, head, dan body karena sudah ada di template utama -->
<div class="content-container">
    <h2>Log Aktivitas</h2>
    
    <div class="user-info">
        <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user']['email']); ?>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Aktivitas</th>
                    <th>IP Address</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_log->num_rows > 0) {
                    $no = 1;
                    while ($row = $result_log->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['aktivitas']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='no-data'>Belum ada aktivitas yang tercatat.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.content-container {
    padding: 20px;
    background-color: #f5f5f5;
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

table th, table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
    white-space: nowrap;
}

table th {
    background-color: #800000;
    color: white;
    font-weight: bold;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #f0f0f0;
}

h2 {
    color: #800000;
    margin-bottom: 20px;
    text-align: center;
}

.user-info {
    margin-bottom: 20px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.no-data {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
}
</style>

<?php
// Menutup prepared statement dan koneksi
$stmt->close();
$log_stmt->close();
mysqli_close($config);
?>