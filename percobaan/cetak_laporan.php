<?php
session_start();
require_once(__DIR__ . '/tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'Laporan Penjualan', 0, true, 'C', 0, '', 0, false, 'M', 'M');
    }
}

// Pastikan error ditampilkan saat development
error_reporting(E_ALL);
ini_set('display_errors', 1);
        // Logo - jika ada
        // $image_file = 'images/logo.png';
        // $this->Image($image_file, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        

// Get date parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nama Toko Anda');
$pdf->SetTitle('Laporan Penjualan');

// Remove header line
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 35, 15);
$pdf->SetHeaderMargin(20);
$pdf->SetFooterMargin(20);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 11);

// Period information
$pdf->Cell(0, 10, 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)), 0, 1, 'L');
$pdf->Ln(5);

// Table header
$header = array('No', 'Tanggal', 'Nama Barang', 'Jumlah', 'Total', 'Kasir');
$widths = array(10, 30, 50, 25, 35, 30);

// Colors for header
$pdf->SetFillColor(128, 0, 0); // Maroon color
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', 'B', 11);

// Print header
foreach($header as $index => $col) {
    $pdf->Cell($widths[$index], 10, $col, 1, 0, 'C', 1);
}
$pdf->Ln();

// Reset text color for data
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 11);

// Get transactions from session
$transactions = [];
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $transaction) {
        $transaction_date = isset($transaction['tanggal']) ? $transaction['tanggal'] : date('Y-m-d');
        if ($transaction_date >= $start_date && $transaction_date <= $end_date) {
            $transactions[] = $transaction;
        }
    }
}

// Print data
$grand_total = 0;
if (!empty($transactions)) {
    foreach($transactions as $index => $transaction) {
        $pdf->Cell($widths[0], 10, $index + 1, 1, 0, 'C');
        $pdf->Cell($widths[1], 10, date('d/m/Y', strtotime($transaction['tanggal'])), 1, 0, 'C');
        $pdf->Cell($widths[2], 10, $transaction['nama_barang'], 1, 0, 'L');
        $pdf->Cell($widths[3], 10, $transaction['jumlah'], 1, 0, 'C');
        $pdf->Cell($widths[4], 10, 'Rp ' . number_format($transaction['total'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell($widths[5], 10, $transaction['kasir'], 1, 0, 'C');
        $pdf->Ln();
        $grand_total += $transaction['total'];
    }
} else {
    $pdf->Cell(array_sum($widths), 10, 'Tidak ada data untuk periode ini', 1, 1, 'C');
}

// Print grand total
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(array_sum(array_slice($widths, 0, 4)), 10, 'Total:', 1, 0, 'R');
$pdf->Cell($widths[4] + $widths[5], 10, 'Rp ' . number_format($grand_total, 0, ',', '.'), 1, 1, 'R');

// Signature section
$pdf->Ln(20);
$pdf->Cell(0, 10, date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 10, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(15);
$pdf->Cell(0, 10, '_________________', 0, 1, 'R');
$pdf->Cell(0, 10, 'Manager', 0, 1, 'R');

// Clean any output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Output PDF
$pdf->Output('laporan_penjualan.pdf', 'I');
exit();
?>