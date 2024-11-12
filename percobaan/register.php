<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        /* Styling untuk body */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Styling untuk form */
        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        /* Styling untuk elemen form */
        h2 {
            text-align: center;
            color: #800000; /* Warna merah maroon */
            margin-bottom: 20px;
        }

        form div {
            margin-bottom: 15px;
        }

        form label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #6c2a2a; /* Warna maroon lebih terang */
        }

        form input[type="text"], 
        form input[type="email"], 
        form input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }

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

        form button:hover {
            background-color: #660000; /* Warna maroon lebih gelap */
        }

        /* Styling untuk teks di bawah tombol */
        p {
            text-align: center;
            font-size: 14px;
            color: #333;
        }

        p a {
            color: #800000; /* Warna merah maroon */
            text-decoration: none;
        }

        p a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<form action="proses.php" method="post">
    <h2>Register</h2>
    <div>
        <label for="username">Username</label>
        <input type="text" name="username" required>
    </div>
    <div>
        <label for="email">Email</label>
        <input type="email" name="email" required>
    </div>
    <div>
        <label for="password">Password</label>
        <input type="password" name="password" required>
    </div>
    <button type="submit" name="register">Register</button>
    <p>Sudah Memiliki Akun? <a href="login.php">Login</a></p>
</form>

</body>
</html>
