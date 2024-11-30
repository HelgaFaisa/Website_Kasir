<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch barang data from database
$query_barang = $config->prepare("SELECT kodebarang, nama_barang, stok, harga_jual, id_kategori FROM barang WHERE stok > 0");
$query_barang->execute();
$result_barang = $query_barang->get_result();
$available_items = [];

while ($row = $result_barang->fetch_assoc()) {
    $available_items[] = $row;
}

// Handle transaction submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    try {
        $config->autocommit(FALSE); // Disable autocommit for transaction

        // Validate CSRF Token
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("CSRF token validation failed");
        }

        // Generate invoice number
        $invoice = "INV" . date('YmdHis');
        $tanggal_input = date('Y-m-d H:i:s');
        $total_transaksi = (float)$_POST['total_semua'];
        $diskon = (float)$_POST['diskon'];
        $bayar = (float)$_POST['bayar'];
        $kembali = (float)$_POST['kembali'];

        // Insert main transaction record
        $stmt_transaksi = $config->prepare("INSERT INTO penjualan (invoice, tanggal_input, total, bayar, kembali, diskon) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_transaksi->bind_param("ssdddd", 
            $invoice, 
            $tanggal_input,  
            $total_transaksi, 
            $bayar, 
            $kembali, 
            $diskon
        );

        
        if (!$stmt_transaksi->execute()) {
            throw new Exception("Gagal menyimpan transaksi: " . $stmt_transaksi->error);
        }
        
        // Get the last inserted penjualan ID
        $id_penjualan = $config->insert_id;

        // Decode cart items from JSON
        $cart_items = json_decode($_POST['cart_items'], true);

        // Prepare detail transaction insert statement
        $stmt_detail = $config->prepare("INSERT INTO detail_penjualan (id_penjualan, kodebarang, jumlah, harga, total) VALUES (?, ?, ?, ?, ?)");

        // Process each cart item
        foreach ($cart_items as $item) {
            $kodebarang = $item['kode'];
            $jumlah = (int)$item['jumlah'];
            $harga = (float)$item['harga'];
            $total_item = $jumlah * $harga;

            // Insert detail transaction
            $stmt_detail->bind_param("issdd", 
                $id_penjualan, 
                $kodebarang, 
                $jumlah, 
                $harga, 
                $total_item
            );

            if (!$stmt_detail->execute()) {
                throw new Exception("Gagal menyimpan detail transaksi: " . $stmt_detail->error);
            }

            // Update barang stok
            $stmt_stok = $config->prepare("UPDATE barang SET stok = stok - ? WHERE kodebarang = ?");
            $stmt_stok->bind_param("is", $jumlah, $kodebarang);
            
            if (!$stmt_stok->execute()) {
                throw new Exception("Gagal update stok: " . $stmt_stok->error);
            }
        }

        $config->commit(); // Commit transaction

        echo json_encode([
            'success' => true,
            'invoice' => $invoice,
            'message' => 'Transaksi berhasil disimpan'
        ]);
        exit;

    } catch (Exception $e) {
        $config->rollback(); // Rollback transaction if failed
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 250px; /* sesuaikan dengan lebar sidebar */
        }

        .container {
    display: flex;
    width: 100%;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .content {
                margin-left: 0;
            }
        }


        .sidebar {
            width: 250px; /* lebar sidebar */
        }

        .content {
            flex-grow: 1; /* memastikan konten utama mengambil sisa ruang */
        }

        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-bg: #f4f4f4;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            line-height: 1.6;
            color: #333;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .kasir-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .search-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
        }

        #searchInput {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        #searchResults {
            max-height: 400px;
            overflow-y: auto;
        }

        #searchResults table {
            width: 100%;
            border-collapse: collapse;
        }

        #searchResults th, 
        #searchResults td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        #cart-items {
            width: 100%;
            border-collapse: collapse;
        }

        #cart-items th, 
        #cart-items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
            border: none;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }

        .payment-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .payment-section input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }

        .kasir-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<?php include('sidebar.php'); ?>
    <div class="container">
        <div class="kasir-section">
            <div class="search-section">
                <h4>Cari Barang</h4>
                <input type="text" id="searchInput" placeholder="Masukkan kode atau nama barang">
                <div id="searchResults"></div>
            </div>

            <div class="cart-section">
                <div class="kasir-header">
                    <h4>KASIR</h4>
                    <button class="btn btn-danger" id="resetCart">Reset Keranjang</button>
                </div>

                <form id="salesForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="cart_items" id="cartItemsInput">
                    <input type="hidden" name="submit" value="1">

                    <div class="date-entries">
                        <label>Tanggal:</label>
                        <input type="text" id="tanggal" readonly>
                    </div>

                    <table id="cart-items">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <div class="payment-section">
                        <div>
                            <label>Total Semua:</label>
                            <input type="text" id="totalSemua" name="total_semua" readonly>
                            
                            <label>Diskon (%):</label>
                            <input type="number" name="diskon" id="diskon" min="0" max="100" value="0">
                            
                            <label>Kembali:</label>
                            <input type="text" id="kembali" name="kembali" readonly>
                        </div>
                        <div>
                            <label>Bayar:</label>
                            <input type="number" id="bayar" name="bayar" required>
                            
                            <div>
                                <button type="submit" class="btn btn-success" id="btnBayar">
                                    <i class="fas fa-money-bill"></i> Bayar
                                </button>
                                <button type="button" class="btn btn-success" id="btnPrint">
                                    <i class="fas fa-print"></i> Print Bukti
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        let cart = [];
        let availableItems = <?php echo json_encode($available_items); ?>;

        // Search functionality
        $('#searchInput').on('keyup', function() {
            let searchTerm = $(this).val().toLowerCase();
            let resultHTML = '<table><thead><tr><th>Kode</th><th>Nama</th><th>Stok</th><th>Harga</th><th>Aksi</th></tr></thead><tbody>';
            
            availableItems.forEach(item => {
                if (item.nama_barang.toLowerCase().includes(searchTerm) || item.kodebarang.toLowerCase().includes(searchTerm)) {
                    resultHTML += `
                        <tr>
                            <td>${item.kodebarang}</td>
                            <td>${item.nama_barang}</td>
                            <td>${item.stok}</td>
                            <td>Rp ${numberFormat(item.harga_jual)}</td>
                            <td>
                                <button class="btn btn-success add-to-cart" 
                                    data-kode="${item.kodebarang}"
                                    data-nama="${item.nama_barang}"
                                    data-stok="${item.stok}"
                                    data-harga="${item.harga_jual}">
                                    Tambah
                                </button>
                            </td>
                        </tr>
                    `;
                }
            });
            
            resultHTML += '</tbody></table>';
            $('#searchResults').html(resultHTML);
        });

        // Add to cart functionality
        $(document).on('click', '.add-to-cart', function() {
            let kode = $(this).data('kode');
            let nama = $(this).data('nama');
            let harga = $(this).data('harga');
            let stok = $(this).data('stok');

            let existingItem = cart.find(item => item.kode === kode);
            if (existingItem) {
                existingItem.jumlah += 1;
            } else {
                cart.push({
                    kode: kode,
                    nama: nama,
                    harga: harga,
                    jumlah: 1
                });
            }
            updateCartDisplay();
        });

        // Update cart display
        function updateCartDisplay() {
            let cartHTML = '';
            cart.forEach((item, index) => {
                let total = item.jumlah * item.harga;
                cartHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nama}</td>
                        <td>
                            <input type="number" value="${item.jumlah}" min="1" 
                                   onchange="updateQuantity(${index}, this.value)">
                        </td>
                        <td>Rp ${numberFormat(item.harga)}</td>
                        <td>Rp ${numberFormat(total)}</td>
                        <td>
                            <button type="button" class="btn btn-danger" onclick="removeItem(${index})">
                                Hapus
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#cart-items tbody').html(cartHTML);
            calculateTotal();
        }

        // Calculate total
        function calculateTotal() {
            let subtotal = cart.reduce((sum, item) => sum + (item.jumlah * item.harga), 0);
            let diskon = parseInt($('#diskon').val()) || 0;
            let total = subtotal - (subtotal * (diskon / 100));
            let bayar = parseFloat($('#bayar').val()) || 0;
            let kembalian = bayar - total;

            $('#totalSemua').val(numberFormat(Math.round(total)));
            $('#kembali').val(numberFormat(Math.max(0, Math.round(kembalian))));
        }

        // Update quantity
        window.updateQuantity = function(index, newQuantity) {
            if (newQuantity > 0) {
                cart[index].jumlah = parseInt(newQuantity);
                updateCartDisplay();
            }
        }

        // Remove item
        window.removeItem = function(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        // Reset cart
        $('#resetCart').click(function() {
            cart = [];
            updateCartDisplay();
        });

        // Handle payment changes
        $('#diskon, #bayar').on('input', calculateTotal);

        // Format numbers
        function numberFormat(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Handle form submission
        $('#salesForm').submit(function(e) {
            e.preventDefault();
            
            if (cart.length === 0) {
                alert('Keranjang masih kosong!');
                return false;
            }

            let total = parseFloat($('#totalSemua').val().replace(/\./g, '')) || 0;
            let bayar = parseFloat($('#bayar').val()) || 0;

            if (bayar < total) {
                alert('Pembayaran kurang!');
                return false;
            }

            // Set hidden input with cart items
            $('#cartItemsInput').val(JSON.stringify(cart));

            // AJAX submission
            $.ajax({
                url: '',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Transaksi berhasil! No Invoice: ' + response.invoice);
                        cart = [];
                        updateCartDisplay();
                        $('#bayar').val('');
                        $('#totalSemua').val('');
                        $('#kembali').val('');
                    } else {
                        alert('Gagal: ' + response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan dalam transaksi');
                }
            });
        });

        // Update date and time
        function updateDateTime() {
            let currentDateTime = new Date();
            let formattedDate = currentDateTime.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            $('#tanggal').val(formattedDate);
        }

        // Call function initially and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
    });
    </script>
</body>
</html>
