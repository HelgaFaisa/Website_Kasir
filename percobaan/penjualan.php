<?php
session_start();

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Sample data barang (in real application, this would come from database)
$data_barang = [
    ['kode' => 'BRG001', 'nama' => 'Baju Kemeja', 'harga' => 150000],
    ['kode' => 'BRG002', 'nama' => 'Celana Jeans', 'harga' => 200000],
    // Add more items as needed
];

// Handle search functionality
$search_results = [];
if (isset($_POST['search_query'])) {
    $query = strtolower($_POST['search_query']);
    foreach ($data_barang as $barang) {
        if (strpos(strtolower($barang['kode']), $query) !== false || 
            strpos(strtolower($barang['nama']), $query) !== false) {
            $search_results[] = $barang;
        }
    }
    echo json_encode($search_results);
    exit;
}

// Handle reset cart
if (isset($_POST['reset_cart'])) {
    $_SESSION['keranjang'] = [];
    exit;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kode_barang'])) {
    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $total = $jumlah * $harga;

    $_SESSION['keranjang'][] = [
        'kode_barang' => $kode_barang,
        'nama_barang' => $nama_barang,
        'jumlah' => $jumlah,
        'harga' => $harga,
        'total' => $total,
        'kasir' => $_SESSION['kasir'] ?? 'Admin'
    ];
}

if (isset($_GET['hapus'])) {
    $index = $_GET['hapus'];
    unset($_SESSION['keranjang'][$index]);
    $_SESSION['keranjang'] = array_values($_SESSION['keranjang']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kasir - Toko Baju</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            margin-bottom: 50px; /* Added space at bottom */
        }

        .page-title {
            color: #800000;
            margin-bottom: 20px;
        }

        .search-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-container {
            display: flex;
            gap: 20px;
        }

        .search-box {
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-results {
            position: absolute;
            background: white;
            width: calc(100% - 40px);
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
            z-index: 1000;
        }

        .search-result-item {
            padding: 10px;
            cursor: pointer;
        }

        .search-result-item:hover {
            background-color: #f5f5f5;
        }

        .hasil-pencarian {
            flex: 1;
            background-color: #800000;
            color: white;
            padding: 10px;
            border-radius: 4px;
        }

        .kasir-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .kasir-header {
            background-color: #800000;
            color: white;
            padding: 15px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .kasir-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reset-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #800000;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        .payment-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .payment-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-input input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex: 1;
        }

        .bayar-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .print-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.querySelector('.search-input');
            const searchResults = document.querySelector('.search-results');
            
            searchInput.addEventListener('input', function() {
                const query = this.value;
                if (query.length > 2) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'search_query=' + encodeURIComponent(query)
                    })
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'search-result-item';
                            div.textContent = `${item.kode} - ${item.nama} (Rp ${item.harga})`;
                            div.onclick = () => addToCart(item);
                            searchResults.appendChild(div);
                        });
                        searchResults.style.display = 'block';
                    });
                } else {
                    searchResults.style.display = 'none';
                }
            });

            // Reset cart functionality
            document.querySelector('.reset-button').addEventListener('click', function() {
                if (confirm('Apakah anda yakin ingin mereset keranjang?')) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'reset_cart=1'
                    })
                    .then(() => location.reload());
                }
            });

            // Calculate total and change
            const totalInput = document.querySelector('[name="total"]');
            const bayarInput = document.querySelector('[name="bayar"]');
            const kembaliInput = document.querySelector('[name="kembali"]');

            bayarInput.addEventListener('input', function() {
                const total = parseInt(totalInput.value) || 0;
                const bayar = parseInt(this.value) || 0;
                const kembali = bayar - total;
                kembaliInput.value = kembali >= 0 ? kembali : 0;
            });

            // Table search functionality
            const tableSearchInput = document.querySelector('.table-search');
            tableSearchInput.addEventListener('input', function() {
                const searchText = this.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                });
            });

            // Calculate initial total
            calculateTotal();
        });

        function calculateTotal() {
            const rows = document.querySelectorAll('tbody tr');
            let total = 0;
            rows.forEach(row => {
                const totalCell = row.querySelector('td:nth-child(4)');
                if (totalCell) {
                    total += parseInt(totalCell.textContent.replace(/[^0-9]/g, '')) || 0;
                }
            });
            document.querySelector('[name="total"]').value = total;
        }

        function addToCart(item) {
            // Implementation of adding item to cart
            // This would typically involve a form submission or AJAX call
            console.log('Adding to cart:', item);
        }
    </script>
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <h1 class="page-title">Keranjang Penjualan</h1>

        <div class="search-section">
            <div class="search-container">
                <div class="search-box">
                    <h2>Cari Barang</h2>
                    <input type="text" class="search-input" placeholder="Masukan : Kode / Nama Barang [ENTER]">
                    <div class="search-results"></div>
                </div>
                <div class="hasil-pencarian">
                    <h2>Hasil Pencarian</h2>
                    <div id="search-results-content"></div>
                </div>
            </div>
        </div>

        <div class="kasir-section">
            <div class="kasir-header">
                <div class="kasir-title">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>KASIR</h2>
                </div>
                <button class="reset-button">RESET KERANJANG</button>
            </div>

            <div>
                <label>Tanggal</label>
                <input type="text" class="date-input" value="<?php echo date('d F Y, H:i'); ?>" readonly>
            </div>

            <div class="table-controls">
                <div class="entries-section">
                    Show 
                    <select>
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                    entries
                </div>
                <div>
                    Search: <input type="text" class="table-search">
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Kasir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($_SESSION['keranjang'])): ?>
                        <?php foreach ($_SESSION['keranjang'] as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><?= htmlspecialchars($item['nama_barang']); ?></td>
                                <td><?= htmlspecialchars($item['jumlah']); ?></td>
                                <td><?= number_format($item['total'], 0, ',', '.'); ?></td>
                                <td><?= htmlspecialchars($item['kasir']); ?></td>
                                <td><a href="?hapus=<?= $index; ?>" class="btn-hapus">Hapus</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No data available in table</td>
                            </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="payment-section">
                <div class="payment-input">
                    <label>Total Semua</label>
                    <input type="text" name="total" readonly>
                </div>
                <div class="payment-input">
                    <label>Bayar</label>
                    <input type="text" name="bayar">
                    <button class="bayar-button">Bayar</button>
                </div>
                <div class="payment-input">
                    <label>Kembali</label>
                    <input type="text" name="kembali" readonly>
                </div>
                <button class="print-button">
                    <i class="fas fa-print"></i> Print Untuk Bukti Pembayaran
                </button>
            </div>
        </div>
    </div>
</body>
</html>