<?php
$config = mysqli_connect("localhost", "root", "", "toko_baju");
if ($config->connect_error) {
    die("Connection failed: " . $config->connect_error);
}

// Validasi dan sanitasi input
$username = mysqli_real_escape_string($config, $_POST['username']);
$email = mysqli_real_escape_string($config, $_POST['email']);
$password = mysqli_real_escape_string($config, $_POST['password']);
$code = md5($email.date('Y-m-d H:i:s'));

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require dirname(__DIR__) . '\vendor\autoload.php';

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("<script>alert('Email tidak valid');window.location='register.php'</script>");
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Create new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'user@example.com';
    $mail->Password   = 'wqbt qnwe bpqq ewwv';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Recipients
    $mail->setFrom('from@testWebsite.com', 'verifikasi');
    $mail->addAddress($email, $username);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Verifikasi Akun';
    $mail->Body    = 'Hi '.$username.'! Terimakasih Telah Melakukan Login Pada Website Kami.
    <br> Selanjutnya Lakukan Verifikasi Akun Anda! <a href="http://localhost/TokoBaju/TokoBaju/verif.php?code='.$code.'">Verifikasi</a>';

    // Send email and insert data if email is sent successfully
    if($mail->send()) {
        $stmt = $config->prepare("INSERT INTO data (username, email, password, verifikasi_code) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $config->error);
        }
        
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $code);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        echo "<script>
            alert('Registrasi berhasil! Silakan cek email Anda untuk verifikasi akun.');
            window.location='login.php';
        </script>";
    }
} catch (Exception $e) {
    echo "<script>
    alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "');
    window.location='register.php';
    </script>";
}

// Close database connection
$config->close();
?>
