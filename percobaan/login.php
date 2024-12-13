<?php 
session_start();
$config = mysqli_connect("localhost", "root", "", "tokobaju");

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Menggunakan prepared statement untuk menghindari SQL Injection
    $stmt = $config->prepare("SELECT * FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $verif = $result->fetch_assoc();
        
        // Verifikasi password dengan hash
        if(password_verify($password, $verif['password'])){
            if($verif['is_verif'] == 1){
                // Menyimpan data user ke session
                $_SESSION['user'] = $verif; // Menyimpan semua data pengguna

                // Tentukan role admin jika email adalah admin
                if ($verif['email'] == 'admin@domain.com') {
                    $_SESSION['user']['role'] = 'admin';  // Menyimpan role admin di session
                } else {
                    $_SESSION['user']['role'] = 'user';  // Menyimpan role user di session
                }

                // Ambil IP address pengguna
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $id_pengguna = $verif['id'];  // Ambil id pengguna dari tabel login
                $aktivitas = "Login berhasil";

                // Menyimpan aktivitas login ke log_aktivitas
                // $log_stmt = $config->prepare("INSERT INTO log_aktivitas (id_pengguna, aktivitas, ip_address) VALUES (?, ?, ?)");
                // $log_stmt->bind_param("iss", $id_pengguna, $aktivitas, $ip_address);
                // $log_stmt->execute();

                header("location:index.php"); // Redirect ke index.php setelah login berhasil
                exit;
            } else {
                $error_message = 'Harap Verifikasi Akun Anda!';
                // Log aktivitas login gagal - Verifikasi akun diperlukan
                $aktivitas = "Login gagal - Verifikasi akun diperlukan";
                $log_stmt = $config->prepare("INSERT INTO log_aktivitas (id_pengguna, aktivitas, ip_address) VALUES (?, ?, ?)");
                $log_stmt->bind_param("iss", $verif['id'], $aktivitas, $_SERVER['REMOTE_ADDR']);
                $log_stmt->execute();
            }
        } else {
            $error_message = 'Password salah!';
            // Log aktivitas login gagal - Password salah
            $aktivitas = "Login gagal - Password salah";
            $log_stmt = $config->prepare("INSERT INTO log_aktivitas (id_pengguna, aktivitas, ip_address) VALUES (?, ?, ?)");
            $log_stmt->bind_param("iss", $verif['id'], $aktivitas, $_SERVER['REMOTE_ADDR']);
            $log_stmt->execute();
        }
    } else {
        $error_message = 'Email tidak terdaftar!';
        // Log aktivitas login gagal - Email tidak terdaftar
        $aktivitas = "Login gagal - Email tidak terdaftar";
        $log_stmt = $config->prepare("INSERT INTO log_aktivitas (id_pengguna, aktivitas, ip_address) VALUES (?, ?, ?)");
        $log_stmt->bind_param("iss", $verif['id'], $aktivitas, $_SERVER['REMOTE_ADDR']);
        $log_stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f8e5e5 0%, #ffe6e6 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(128, 0, 0, 0.15);
            width: 320px;
            border: 1px solid #ffeded;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #800000;
            font-size: 28px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #800000;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ffcccc;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #800000;
            box-shadow: 0 0 5px rgba(128, 0, 0, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background-color: #800000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #600000;
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #800000;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: #600000;
            text-decoration: underline;
        }

        .error-message {
            color: #d63031;
            text-align: center;
            font-size: 14px;
            margin-top: 15px;
        }

        /* Tambahan efek hover untuk input */
        .form-group input:hover {
            border-color: #cc0000;
        }

        /* Efek focus untuk container */
        .login-container:hover {
            box-shadow: 0 12px 28px rgba(128, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Login</h2>
        </div>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="submit-btn">Login</button>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <div class="forgot-password">
                <a href="forgot_password.php">Lupa Password?</a>
            </div>
        </form>
    </div>
</body>
</html>