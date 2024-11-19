<?php
// Pastikan composer autoload tersedia
require_once 'vendor/autoload.php';

// Mulai session jika diperlukan
session_start();

use Picqer\Barcode\BarcodeGeneratorHTML;

class BarcodeGenerator {
    private $generator;

    public function __construct() {
        $this->generator = new BarcodeGeneratorHTML();
    }

    public function generate($kode) {
        try {
            return $this->generator->getBarcode($kode, $this->generator::TYPE_CODE_128);
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Generator</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f6f9;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            background-color: #800000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #990000;
        }
        
        .barcode-container {
            margin-top: 20px;
            text-align: center;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .error {
            color: red;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid red;
            border-radius: 4px;
            background-color: #fff5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Barcode Generator</h1>
        
        <form method="GET" class="form-group">
            <input type="text" name="kode" placeholder="Masukkan kode untuk generate barcode" 
                   value="<?= isset($_GET['kode']) ? htmlspecialchars($_GET['kode']) : '' ?>" required>
            <button type="submit">Generate Barcode</button>
        </form>

        <?php
        if (isset($_GET['kode'])) {
            try {
                $barcodeGen = new BarcodeGenerator();
                $barcode = $barcodeGen->generate($_GET['kode']);
                ?>
                <div class="barcode-container">
                    <h3>Kode: <?= htmlspecialchars($_GET['kode']) ?></h3>
                    <?= $barcode ?>
                </div>
                <?php
            } catch (Exception $e) {
                echo '<div class="error">Error: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
    </div>
</body>
</html>