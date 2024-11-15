<?php
// Security headers
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = mysqli_connect("localhost", "root", "", "tokobaju");
if (!$config) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require dirname(__DIR__) . '/vendor/autoload.php';

if(isset($_POST['forgot_password'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
            alert('Format email tidak valid.');
            window.location='forgot_password.php';
        </script>";
        exit();
    }
    
    // Check if email exists
    $stmt = $config->prepare("SELECT id, username FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        try {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $update_stmt = $config->prepare("UPDATE login SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            $update_stmt->bind_param("sss", $reset_token, $reset_expiry, $email);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Database error: " . $update_stmt->error);
            }
            
            // Send reset email
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'miftaoktay@gmail.com'; // Ganti dengan email Anda
            $mail->Password = 'wqbt qnwe bpqq ewwv'; // Ganti dengan password aplikasi Gmail Anda
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            // Recipients
            $mail->setFrom('testverifikasi.email@gmail.com', 'Reset Password');
            $mail->addAddress($email, $user['username']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password';
            $resetLink = "http://localhost/percobaan/percobaan/reset_password.php?token=" . $reset_token;
            
            $mail->Body = '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #800000;">Halo '.$user['username'].'!</h2>
                        <p>Kami menerima permintaan untuk mengatur ulang password Anda.</p>
                        <p>Klik tombol di bawah ini untuk mengatur ulang password:</p>
                        <p style="text-align: center;">
                            <a href="'.$resetLink.'" 
                               style="background-color: #800000; 
                                      color: white; 
                                      padding: 10px 20px; 
                                      text-decoration: none; 
                                      border-radius: 5px;
                                      display: inline-block;">
                                Reset Password
                            </a>
                        </p>
                        <p>Link ini akan kadaluarsa dalam 1 jam.</p>
                        <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                        <hr>
                        <p style="font-size: 12px; color: #666;">
                            Email ini dikirim secara otomatis, mohon jangan balas email ini.
                        </p>
                    </div>
                </body>
                </html>';
            
            $mail->AltBody = "Halo {$user['username']}!\n\n" .
                            "Kami menerima permintaan untuk mengatur ulang password Anda.\n" .
                            "Klik link berikut untuk mengatur ulang password:\n" .
                            $resetLink . "\n\n" .
                            "Link ini akan kadaluarsa dalam 1 jam.\n" .
                            "Jika Anda tidak meminta reset password, abaikan email ini.";
            
            if($mail->send()) {
                echo "<script>
                    alert('Instruksi reset password telah dikirim ke email Anda.');
                    window.location='login.php';
                </script>";
            }
        } catch (Exception $e) {
            error_log("Password Reset Error: " . $e->getMessage());
            echo "<script>
                alert('Terjadi kesalahan saat mengirim email. Silakan coba lagi nanti.');
                window.location='forgot_password.php';
            </script>";
        }
    } else {
        // Untuk keamanan, tetap tampilkan pesan yang sama meskipun email tidak ditemukan
        echo "<script>
            alert('Jika email terdaftar, instruksi reset password akan dikirim.');
            window.location='forgot_password.php';
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        
        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        
        h2 {
            text-align: center;
            color: #800000;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #6c2a2a;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        button {
            width: 100%;
            padding: 10px;
            background-color: #800000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        button:hover {
            background-color: #660000;
        }
        
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .back-link a {
            color: #800000;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #660000;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <form action="forgot_password.php" method="post">
        <h2>Lupa Password</h2>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required 
                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" 
                   title="Masukkan alamat email yang valid">
        </div>
        <button type="submit" name="forgot_password">Reset Password</button>
        <div class="back-link">
            <a href="login.php">Kembali ke Login</a>
        </div>
    </form>
</body>
</html>