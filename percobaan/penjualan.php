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

            // Check if there's enough stock
            $stmt_check_stock = $config->prepare("SELECT stok FROM barang WHERE kodebarang = ?");
            $stmt_check_stock->bind_param("s", $kodebarang);
            $stmt_check_stock->execute();
            $result_stock = $stmt_check_stock->get_result();
            $stock_data = $result_stock->fetch_assoc();

            if ($stock_data['stok'] < $jumlah) {
                throw new Exception("Stok barang {$item['nama']} tidak cukup.");
            }

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

        $formattedTotal = number_format($total_transaksi, 0, ',', '.');
$formattedBayar = number_format($bayar, 0, ',', '.');
$formattedKembali = number_format($kembali, 0, ',', '.');

echo json_encode([
    'success' => true,
    'invoice' => $invoice,
    'total' => $formattedTotal,
    'bayar' => $formattedBayar,
    'kembali' => $formattedKembali,
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
/* Global Styles */
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f9f9f9;
    color: #333;
    line-height: 1.6;
    margin: 0;
}

.container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    min-height: 100vh;
    margin-left: 240px; /* Sesuaikan dengan sidebar */
    transition: margin-left 0.3s ease;
}

header {
    background-color: #800000;
    color: white;
    text-align: center;
    padding: 15px;
    font-size: 20px;
    font-weight: bold;
    border-bottom: 2px solid #5a0000;
}

/* Kasir Section */
.kasir-section {
    flex: 1;
    background: #f9f9f9;
    border-radius: 20px; /* Ujung melengkung */
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Tambahkan bayangan */
}

.kasir-section h4 {
    font-size: 22px;
    color: #800000;
    border-bottom: 3px solid #800000; /* Garis bawah lebih tebal */
    padding-bottom: 8px;
    text-align: center;
}

.kasir-section:hover {
    transform: translateY(-5px); /* Efek hover: naik sedikit */
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); /* Perbesar bayangan */
}


.kasir-header h4 {
    font-size: 26px;
    color: #800000;
    border-bottom: 3px solid #800000; /* Garis bawah lebih tebal */
    padding-bottom: 10px;
    text-align: center;
}

.kasir-input {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.kasir-input input, .kasir-input select {
    width: 95%; /* Ukuran input */
    max-width: 700px;
    padding: 12px;
    font-size: 16px;
    border: 2px solid #ccc;
    border-radius: 20px;
    transition: all 0.3s ease;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1); /* Tambahkan bayangan dalam */
}

.kasir-input input:focus, .kasir-input select:focus {
    outline: none;
    border-color: #800000; /* Warna saat fokus */
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 0 8px rgba(128, 0, 0, 0.5); /* Efek glow saat fokus */
}

.kasir-input input:hover, .kasir-input select:hover {
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 0 5px rgba(128, 0, 0, 0.3); /* Efek glow saat hover */
}

/* Tombol di Kasir */
.kasir-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.kasir-buttons .btn-primary {
    background-color: #800000;
    color: white;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    border-radius: 20px; /* Tombol ujung melengkung */
    cursor: pointer;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.kasir-buttons .btn-primary:hover {
    background-color: #5a0000;
    box-shadow: 0 4px 8px rgba(128, 0, 0, 0.3); /* Tambahkan bayangan hover */
}

/* Tabel Kasir */
.kasir-table {
    width: 100%;
    border-collapse: separate; /* Tetap menggunakan separate untuk border-radius */
    border-spacing: 0; /* Pastikan tidak ada jarak antar sel */
    margin-top: 15px;
    border: 1px solid #800000;
    border-radius: 10px; /* Tepi tabel melengkung */
    overflow: hidden; /* Pastikan radius diterapkan dengan baik */
}

.kasir-table th:first-child {
    border-top-left-radius: 10px; /* Ujung kiri atas */
}

.kasir-table th:last-child {
    border-top-right-radius: 10px; /* Ujung kanan atas */
}

.kasir-table th, .kasir-table td {
    padding: 12px;
    border: 1px solid #800000;
    text-align: center;
    font-size: 16px;
}

.kasir-table th {
    background-color: #800000;
    color: white;
    font-size: 18px;
    padding: 12px;
    text-align: center;
}


.kasir-table tr:last-child td:first-child {
    border-bottom-left-radius: 10px; /* Ujung kiri bawah */
}

.kasir-table tr:last-child td:last-child {
    border-bottom-right-radius: 10px; /* Ujung kanan bawah */
}

.kasir-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.kasir-table tr:hover {
    background-color: #f1f1f1;
}


/* Search Section */
.search-section {
    flex: 1;
    background: #f9f9f9;
    border-radius: 10px; /* Ujung lebih melengkung */
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Tambahan bayangan */
}

.search-section h4 {
    font-size: 22px;
    color: #800000;
    border-bottom: 3px solid #800000; /* Garis bawah lebih tebal */
    padding-bottom: 8px;
}

#searchInput {
    width: 95%;
    max-width: 700px;
    padding: 12px;
    font-size: 16px;
    border: 2px solid #ccc;
    border-radius: 20px;
    margin-top: 10px;
    transition: all 0.3s ease; /* Efek transisi */
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1); /* Tambahkan bayangan dalam */
}

#searchInput:focus {
    outline: none;
    border-color: #800000; /* Warna saat fokus */
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 0 8px rgba(128, 0, 0, 0.5); /* Efek glow saat fokus */
}

#searchResults table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    border: 2px solid #800000;
    border-radius: 10px; /* Ujung tabel melengkung */
    overflow: hidden; /* Untuk efek border-radius */
}

#searchResults th, #searchResults td {
    padding: 12px;
    border: 1px solid #800000;
    text-align: center;
}

#searchResults th {
    background-color: #800000;
    color: white;
    font-size: 16px;
}

#searchResults tr:nth-child(even) {
    background-color: #f9f9f9;
}

#searchResults tr:hover {
    background-color: #f1f1f1;
}

#searchInput:hover {
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 0 5px rgba(128, 0, 0, 0.3); /* Efek glow saat hover */
}

/* Cart Section */
.cart-section {
    flex: 1;
    background: #f4f4f4;
    border-radius: 6px;
    padding: 15px;
}

#cart-items {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 10px;
    border: 2px solid #800000;
    border-radius: 10px; /* Tambahkan ini */
    overflow: hidden; /* Tambahkan ini */
}

#cart-items th:first-child {
    border-top-left-radius: 10px;
}

#cart-items th:last-child {
    border-top-right-radius: 10px;
}

#cart-items tr:last-child td:first-child {
    border-bottom-left-radius: 10px;
}

#cart-items tr:last-child td:last-child {
    border-bottom-right-radius: 10px;
}

#cart-items th, #cart-items td {
    padding: 10px;
    text-align: center;
}

#cart-items th {
    background-color: #800000;
    color: white;
}

#cart-items tr:nth-child(even) {
    background-color: #f2f2f2;
}

#cart-items tr:hover {
    background-color: #f1f1f1;
}

/* Payment Section */
.payment-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 20px;
}

.payment-section input {
    padding: 8px;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

.payment-section .btn-success {
    grid-column: span 2;
    margin-top: 10px;
    padding: 10px 15px;
    font-size: 16px;
}

/* Button Styles */
.btn {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #ff4d4d;
    color: white;
}

.btn-danger:hover {
    background-color: #ff3333;
}

.btn-primary {
    background-color: #800000;
    color: white;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow: auto;
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #800000;
    width: 80%;
    max-width: 500px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

/* Responsiveness */
@media (max-width: 768px) {
    .container {
        margin-left: 0;
        flex-direction: column;
        padding: 10px;
    }

    .payment-section {
        grid-template-columns: 1fr;
    }

    .modal-content {
        width: 95%;
        margin: 10% auto;
    }

    #cart-items th, #cart-items td {
        font-size: 12px;
    }

    #searchInput {
        font-size: 14px;
    }

    .payment-section .btn-success {
        grid-column: 1;
    }
}

</style>
</head>
<body>
    <?php include('sidebar.php'); ?>
        <div class="container">
            <div class="search-section">
                <h4><i class="fas fa-search"></i> Cari Barang</h4>
                <input type="text" id="searchInput" placeholder="Masukkan kode atau nama barang">
                <div id="searchResults"></div>
            </div>
            <div class="cart-section">
                <div class="kasir-header">
                    <h4><i class="fas fa-cash-register"></i> KASIR</h4>
                    <button class="btn btn-danger" id="resetCart">
                        <i class="fas fa-trash"></i> Reset Keranjang
                    </button>
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
    if (searchTerm === '') {
        $('#searchResults').html(''); // Kosongkan hasil jika input kosong
        return;
    }
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
                            <i class="fas fa-plus"></i>
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
        $(document).on('click', '.add-to-cart', function(e) {
    e.preventDefault(); // Mencegah aksi default (refresh halaman)

    let kode = $(this).data('kode');
    let nama = $(this).data('nama');
    let harga = $(this).data('harga');

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

    // Perbarui tabel transaksi
    updateCartDisplay();

    // Kosongkan hasil pencarian dan input pencarian
    $('#searchResults').html('');
    $('#searchInput').val('');
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
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#cart-items tbody').html(cartHTML);
            calculateTotal();
        }
        
        function calculateTotal() {
            let total = 0;
            cart.forEach(item => {
                total += item.jumlah * item.harga;
            });

            let diskon = parseFloat(document.getElementById("diskon").value) || 0;
            let totalAfterDiscount = total - (total * (diskon / 100));
            let bayar = parseFloat(document.getElementById("bayar").value) || 0;
            let kembali = bayar - totalAfterDiscount;

            // Ensure values are numeric and not NaN
            totalAfterDiscount = isNaN(totalAfterDiscount) ? 0 : totalAfterDiscount;
            kembali = isNaN(kembali) ? 0 : kembali;

            document.getElementById("totalSemua").value = numberFormat(totalAfterDiscount);
            document.getElementById("kembali").value = numberFormat(kembali >= 0 ? kembali : 0);

            // Return these values for use in print receipt
            return {
                total: totalAfterDiscount,
                kembali: kembali >= 0 ? kembali : 0
            };
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
            // Reset nilai input lainnya
            $('#diskon').val(0); // Reset diskon ke 0
            $('#bayar').val(''); // Kosongkan input bayar
            $('#totalSemua').val(''); // Kosongkan total
            $('#kembali').val(''); // Kosongkan kembalian
        });

        $('#diskon').on('input', function () {
    let diskon = parseFloat($(this).val()) || 0;
    if (diskon < 0) diskon = 0;
    if (diskon > 100) diskon = 100;
    $(this).val(diskon); 
    calculateTotal();
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

        // Get calculated values
        let calculatedValues = calculateTotal();
        let total = calculatedValues.total;
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
                    
                    // Pass actual calculated values to print receipt
                    printReceipt(
                        response.invoice, 
                        calculatedValues.total, 
                        bayar, 
                        calculatedValues.kembali
                    );

                    // Reset form and cart
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
 // Print receipt function
function printReceipt(invoice, total, bayar, kembali) {
    // Format data into currency (Rupiah)
    const formattedTotal = numberFormat(total);
    const formattedBayar = numberFormat(bayar);
    const formattedKembali = numberFormat(kembali);

    const storeName = "AdaAllshop";
    const storeAddress = "Dusun Sumberjo, Yosorati, Sumberbaru, Jember";
    const storePhone = "082257079817";

    // Prepare the receipt content
    let receipt = `STRUK PEMBAYARAN\n`;
    receipt += `Nama Toko: ${storeName}\n`;
    receipt += `Alamat: ${storeAddress}\n`;
    receipt += `Telepon: ${storePhone}\n\n`;

    // Adding the items to the receipt (assuming you have the cart available)
    cart.forEach(item => {
        const itemTotal = item.jumlah * item.harga;
        receipt += `${item.nama}    ${item.jumlah} x Rp ${numberFormat(item.harga)} = Rp ${numberFormat(itemTotal)}\n`;
    });

    // Adding totals, bayar and kembali
    receipt += `\nTotal Rp.     ${formattedTotal}\n`;
    receipt += `Bayar Rp.     ${formattedBayar}\n`;
    receipt += `Kembali Rp.   ${formattedKembali}\n`;

    receipt += `\nBarang yang sudah dibeli tidak dapat ditukar / dikembalikan.\n`;
    receipt += `====== ${new Date().toISOString().slice(0, 19).replace('T', ' ')} ======\n`;

    // Display or print the receipt
    console.log(receipt); // This is just a simple log, you might want to open a print window.
    
    // Trigger print dialog (this will print the receipt to the printer)
    let printWindow = window.open('', '', 'height=400,width=600');
    printWindow.document.write('<pre>' + receipt + '</pre>');
    printWindow.document.close();
    printWindow.print();
}

// Fungsi untuk memformat angka menjadi Rupiah (IDR)
function numberFormat(value) {
    return value.toLocaleString('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });
}
// Panggil fungsi untuk mencetak struk
printReceipt('INV-001', items, bayar);
    });

        // Update date and time
        function updateDateTime() {
    let currentDateTime = new Date();
    
    // Ambil bagian tanggal
    let day = String(currentDateTime.getDate()).padStart(2, '0');
    let month = String(currentDateTime.getMonth() + 1).padStart(2, '0'); // Bulan dimulai dari 0
    let year = currentDateTime.getFullYear();

    // Ambil bagian waktu
    let hours = String(currentDateTime.getHours()).padStart(2, '0');
    let minutes = String(currentDateTime.getMinutes()).padStart(2, '0');

    // Format akhir
    let formattedDate = `${day}/${month}/${year} jam ${hours}:${minutes}`;

    // Tampilkan ke input dengan id 'tanggal'
    $('#tanggal').val(formattedDate);
}

// Panggil fungsi pertama kali, lalu setiap 1 detik
updateDateTime();
setInterval(updateDateTime, 1000);

// Fungsi untuk membuka modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

// Fungsi untuk menutup modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Menutup modal jika klik di luar area modal
window.onclick = function(event) {
    const modal = document.querySelector('.modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

    </script>
</body>
</html>