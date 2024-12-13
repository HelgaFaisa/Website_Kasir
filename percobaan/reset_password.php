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

// Koneksi database
$config = mysqli_connect("localhost", "root", "", "tokobaju");
if (!$config) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah token valid
if (isset($_GET['token'])) {
    $reset_token = $_GET['token'];

    // Cek token di database
    $stmt = $config->prepare("SELECT id, email, reset_expiry FROM login WHERE reset_token = ?");
    $stmt->bind_param("s", $reset_token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $expiry_time = strtotime($user['reset_expiry']);

        // Jika token kadaluarsa
        if (time() > $expiry_time) {
            echo "<script>
                    alert('Link reset password sudah kadaluarsa. Silakan minta link baru.');
                    window.location='forgot_password.php';
                </script>";
            exit();
        }

        // Token valid, form untuk reset password
        if (isset($_POST['reset_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validasi password
            if ($new_password !== $confirm_password) {
                echo "<script>
                        alert('Password dan konfirmasi password tidak cocok.');
                    </script>";
            } else {
                // Enkripsi password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Perbarui password di database dan hapus token
                $update_stmt = $config->prepare("UPDATE login SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?");
                $update_stmt->bind_param("ss", $hashed_password, $reset_token);

                if ($update_stmt->execute()) {
                    echo "<script>
                            alert('Password Anda berhasil diperbarui.');
                            window.location='login.php';
                        </script>";
                } else {
                    echo "<script>
                            alert('Terjadi kesalahan. Silakan coba lagi.');
                        </script>";
                }
            }
        }
    } else {
        echo "<script>
                alert('Token tidak valid.');
                window.location='forgot_password.php';
            </script>";
        exit();
    }
} else {
    // Jika tidak ada token
    echo "<script>
            alert('Token tidak ditemukan.');
            window.location='forgot_password.php';
        </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        input[type="password"] {
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
    <form action="reset_password.php?token=<?php echo $reset_token; ?>" method="post">
        <h2>Reset Password</h2>
        <div class="form-group">
            <label for="new_password">Password Baru</label>
            <input type="password" id="new_password" name="new_password" required />
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required />
        </div>
        <button type="submit" name="reset_password">Reset Password</button>
        <div class="back-link">
            <a href="login.php">Kembali ke Login</a>
        </div>
    </form>
</body>
</html>
