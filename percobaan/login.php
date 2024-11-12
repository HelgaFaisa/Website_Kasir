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
                $_SESSION['user'] = $verif; // Menyimpan data user ke session
                header("location:index.php"); // Redirect ke index.php setelah login berhasil
                exit;
            } else {
                $error_message = 'Harap Verifikasi Akun Anda!';
            }
        } else {
            $error_message = 'Password salah!';
        }
    } else {
        $error_message = 'Email tidak terdaftar!';
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
        /* Mengatur styling dasar untuk body */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Mengatur styling untuk form */
        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        /* Styling untuk judul dan elemen form */
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        form div {
            margin-bottom: 15px;
        }

        /* Styling untuk label input */
        form label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #6c2a2a; /* Warna maroon */
        }

        /* Styling untuk input field */
        form input[type="text"], form input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }

        /* Styling untuk tombol */
        form button {
            width: 100%;
            padding: 12px;
            background-color: #800000; /* Warna merah maroon */
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        /* Efek hover untuk tombol */
        form button:hover {
            background-color: #660000; /* Warna maroon lebih gelap */
        }

        /* Styling untuk pesan kesalahan */
        p {
            text-align: center;
            font-size: 14px;
            color: red; /* Warna merah untuk pesan kesalahan */
        }
    </style>
</head>
<body>

<form action="login.php" method="post">
    <h2>Login</h2>
    <div>
        <label for="email">Email</label>
        <input type="text" name="email" required>
    </div>
    <div>
        <label for="password">Password</label>
        <input type="password" name="password" required>
    </div>
    <button type="submit" name="login">Login</button>
    <!-- Menampilkan pesan kesalahan jika ada -->
    <?php if (isset($error_message)): ?>
        <p><?php echo $error_message; ?></p>
    <?php endif; ?>
</form>

</body>
</html>