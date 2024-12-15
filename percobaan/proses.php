<?php
$config = mysqli_connect("localhost", "root", "", "tokobaju");
if ($config->connect_error) {
    die("Connection failed: " . $config->connect_error);
}

// Validasi dan sanitasi input
$username = mysqli_real_escape_string($config, $_POST['username']);
$email = mysqli_real_escape_string($config, $_POST['email']);
$password = mysqli_real_escape_string($config, $_POST['password']);

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';  // Pastikan path sesuai dengan struktur folder kamu


// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("<script>alert('Email tidak valid');window.location='register.php'</script>");
}

// Generate verification code
$code = md5($email.date('Y-m-d H:i:s'));

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if email already exists
$check_email = $config->prepare("SELECT email FROM login WHERE email = ?");
$check_email->bind_param("s", $email);
$check_email->execute();
$result = $check_email->get_result();
if ($result->num_rows > 0) {
    die("<script>alert('Email sudah terdaftar!');window.location='register.php'</script>");
}
$check_email->close();

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'miftaoktay@gmail.com';
    $mail->Password   = 'wqbt qnwe bpqq ewwv';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Recipients
    $mail->setFrom('testverifikasi.email@gmail.com', 'Verifikasi Akun');
    $mail->addAddress($email, $username);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Verifikasi Akun';
    $mail->Body    = '
        <html>
        <body>
            <h2>Halo '.$username.'!</h2>
            <p>Terima kasih telah mendaftar pada website kami.</p>
            <p>Silakan klik tombol di bawah ini untuk memverifikasi akun Anda:</p>
            <p>
                <a href="http://localhost/webkasir/Website_Kasir/percobaan/verif.php?code='.$code.'" 
                   style="background-color: #4CAF50; 
                          color: white; 
                          padding: 10px 20px; 
                          text-decoration: none; 
                          border-radius: 5px;">
                    Verifikasi Akun
                </a>
            </p>
            <p>Jika tombol tidak berfungsi, salin dan tempel link berikut di browser Anda:</p>
            <p>http://localhost/percobaan/percobaan/verif.php?code='.$code.'</p>
        </body>
        </html>';

    // Send email and insert data
    if ($mail->send()) {
        $stmt = $config->prepare("INSERT INTO login (username, email, password, verifikasi_code, is_verif) VALUES (?, ?, ?, ?, 0)");
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

$config->close();
?>
