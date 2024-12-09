<?php
session_start();
require_once 'config.php';
require_once 'TCPDF-main/tcpdf.php'; 
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Tidak ada ID barang yang ditentukan");
}

$id_barang = intval($_GET['id']);

if (!isset($config) || !($config instanceof mysqli)) {
    die("Koneksi database tidak valid");
}

try {
    $stmt = $config->prepare("SELECT * FROM barang WHERE id_barang = ?");
    $stmt->bind_param("i", $id_barang);
    $stmt->execute();
    $result = $stmt->get_result();
    $barang = $result->fetch_assoc();

    if (!$barang) {
        die("Barang tidak ditemukan");
    }
} catch (Exception $e) {
    die("Kesalahan dalam mengambil data: " . $e->getMessage());
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Toko Baju');
$pdf->SetTitle('Cetak Barcode ' . $barang['nama_barang']);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Atur margin (15-20mm sesuai saran)
$pdf->SetMargins(15, 15, 15, 15);

// Atur page break otomatis
$pdf->SetAutoPageBreak(TRUE, 15);

// Konfigurasi layout barcode
$barcodes_per_row = 3;     // Jumlah barcode per baris
$rows_per_page = 4;        // Jumlah baris per halaman
$barcodes_per_page = $barcodes_per_row * $rows_per_page;  // Total barcode per halaman

// Hitung jumlah halaman yang dibutuhkan
$stok = $barang['stok'];
$pages_needed = ceil($stok / $barcodes_per_page);

// Fungsi generate barcode
function generateBarcode($pdf, $code, $x, $y, $barang) {
    $style = array(
        'position' => '',
        'align' => 'C',
        'stretch' => false,
        'fitwidth' => true,
        'cellfitalign' => '',
        'border' => true,
        'hpadding' => 'auto',
        'vpadding' => 'auto',
        'fgcolor' => array(0, 0, 0),
        'bgcolor' => false,
        'text' => true,
        'font' => 'helvetica',
        'fontsize' => 8,
        'stretchtext' => 4
    );

    $pdf->SetFont('helvetica', '', 8);
    $pdf->Text($x + 5, $y - 8, $barang['nama_barang']);
    
    $pdf->write1DBarcode($code, 'C128', $x, $y, 55, 20, 0.4, $style, 'N');
}

for ($page = 0; $page < $pages_needed; $page++) {
    $pdf->AddPage();
    
    $start = $page * $barcodes_per_page;
    $end = min($start + $barcodes_per_page, $stok);
    
    $row = 0;
    $col = 0;
    
    for ($i = $start; $i < $end; $i++) {
        // Gunakan kode barang asli tanpa menambahkan nomor urut
        $unique_code = $barang['kodebarang'];
        
        // Hitung posisi
        $x = 15 + ($col * 65);  // Jarak horizontal 65mm
        $y = 15 + ($row * 40);  // Jarak vertikal 40mm
        
        // Generate barcode
        generateBarcode($pdf, $unique_code, $x, $y, $barang);
        
        $col++;
        if ($col >= $barcodes_per_row) {
            $col = 0;
            $row++;
        }
    }
}

$pdf->Output('barcode_' . $barang['nama_barang'] . '.pdf', 'I');
?>