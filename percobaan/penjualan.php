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
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f9f9f9;
    color: #333;
    line-height: 1.6;
    margin: 0;
}
.container {
    max-width: 100%;
    margin: 20px auto; /* Pusatkan konten */
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
@media (max-width: 576px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .container {
        margin-left: 0;
        padding: 10px;
    }

    .content-container {
        margin-left: 180px;
    }
}

.container {
    max-width: 1200px; /* Lebar maksimum */
    margin: 20px auto; /* Pusatkan konten */
    padding: 20px;
    display: flex; /* Gunakan flex untuk layout */
    flex-wrap: wrap;
    gap: 20px; /* Beri jarak antar elemen */
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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

.kasir-section {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
.sidebar {
    width: 220px; /* Sesuaikan lebar sidebar */
    position: fixed; /* Sidebar tetap di tempat */
    top: 0;
    left: 0;
    height: 100%;
    background-color: #f8f8f8;
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 20px;
}
.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    margin: 15px 0;
}

.sidebar ul li a {
    text-decoration: none;
    color: #333;
    display: block;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.sidebar ul li a:hover {
    background-color: #ddd;
}

.search-section,
.cart-section {
    flex: 1;
    background: #f4f4f4;
    border-radius: 6px;
    padding: 15px;
}

.search-section h4,
.cart-section h4 {
    margin-bottom: 15px;
    color: #800000; /* Maroon */
    font-weight: bold;
}

#searchInput {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 16px;
}

#searchResults table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

#searchResults th,
#searchResults td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

#cart-items {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    border: 2px solid #800000;
}

#cart-items th,
#cart-items td {
    padding: 10px;
    border: 1px solid #800000;
    text-align: center;
}
.payment-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 20px;
}

.payment-section input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.btn {
    display: inline-block;
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
    padding: 10px 15px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-primary {
    background-color: #800000; /* Maroon */
    color: white;
}

#resetCart {
    background-color: #800000; /* Maroon */
    color: white;
}
#cart-items {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    border: 2px solid #800000; /* Maroon border for the table */
}

#cart-items th, #cart-items td {
    padding: 10px;
    border: 1px solid #800000; /* Maroon border for table cells */
    text-align: center;
}

/* Table header (th) background color and text color */
#cart-items th {
    background-color: #800000; /* Maroon background for headers */
    color: white; /* White text color for contrast */
}

/* Table rows */
#cart-items tr:nth-child(even) {
    background-color: #f2f2f2; /* Alternate row background for readability */
}

/* Table row hover effect for better interaction */
#cart-items tr:hover {
    background-color: #f1f1f1;
}

.container {
    margin-left: 240px;
    padding: 20px;
    background-color: #fff;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

.kasir-section {
    background-color: #fff; /* White background for kasir section */
    padding: 20px;
    border-radius: 10px;
}

.kasir-header h4 {
    font-size: 24px;
    color: #800000; /* Maroon color for the header */
    border-bottom: 2px solid #800000; /* Maroon underline */
    padding-bottom: 10px;
}

.search-section {
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 5px;
}

.search-section h4 {
    font-size: 20px;
    color: #800000; /* Maroon color for the search title */
}

.search-section input {
    padding: 10px;
    width: 100%;
    border: none; /* Removed the maroon border for search input */
    border-radius: 5px;
    margin-top: 10px;
}

#searchResults {
    margin-top: 10px;
    padding: 10px;
    border-radius: 5px;
}

.btn-danger {
    background-color: #ff4d4d; /* Red background for reset button */
    color: white;
    border: none; /* Removed the maroon border around the reset button */
    border-radius: 5px;
}

.btn-danger:hover {
    background-color: #ff3333; /* Darker red on hover */
}
/* Style for the search results table */
#searchResults table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    border: 2px solid #800000; /* Maroon border for the table */
}

#searchResults th,
#searchResults td {
    padding: 10px;
    border: 1px solid #800000; /* Maroon border for table cells */
    text-align: center;
}

#searchResults th {
    background-color: #800000; /* Maroon background for header */
    color: white; /* White text color for header */
}

#searchResults tr:nth-child(even) {
    background-color: #f9f9f9; /* Lighter background for even rows */
}

#searchResults tr:hover {
    background-color: #f1f1f1; /* Slightly darker background for hover effect */
}

@media (max-width: 768px) {
    .container {
        flex-direction: column;
        padding: 10px;
    }

    .search-section,
    .cart-section {
        flex: 1;
    }

    .sidebar {
        width: 100%;
        position: relative;
        padding: 15px;
    }
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
                                <!-- <button type="button" class="btn btn-success" id="btnPrint">
                                    <i class="fas fa-print"></i> Print Bukti
                                </button> -->
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
    console.log("Searching for:", searchTerm);  // Debugging log
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
    </script>
</body>
</html>