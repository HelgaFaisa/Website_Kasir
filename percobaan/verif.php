<?php
session_start();
$config = mysqli_connect("localhost", "root", "", "tokobaju");

if ($config->connect_error) {
    die("Connection failed: " . $config->connect_error);
}

$code = $_GET['code'] ?? '';

if (!empty($code)) {
    // Validasi kode verifikasi
    $qry = $config->query("SELECT * FROM login WHERE verifikasi_code='$code' AND is_verif=0");
    if ($qry && $qry->num_rows > 0) {
        $result = $qry->fetch_assoc();

        // Update status verifikasi
        $update = $config->query("UPDATE login SET is_verif=1 WHERE id='" . $result['id'] . "'");
        if ($update) {
            echo "<script>
                alert('Verifikasi Berhasil, silakan login');
                window.location='login.php';
            </script>";
        } else {
            echo "<script>
                alert('Terjadi kesalahan saat memverifikasi akun');
                window.location='register.php';
            </script>";
        }
    } else {
        echo "<script>
            alert('Kode verifikasi tidak valid atau akun sudah diverifikasi');
            window.location='register.php';
        </script>";
    }
} else {
    echo "<script>
        alert('Kode verifikasi tidak ditemukan');
        window.location='register.php';
    </script>";
}
?>
