<?php
// cetak_laporan_pdf.php
require_once 'config.php';
require_once 'TCPDF-main/tcpdf.php';

// Date filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Retrieve restock data
$restock_data = [];
$query = "SELECT r.*, s.nama_supplier 
          FROM restock r 
          LEFT JOIN supplier s ON r.id_supplier = s.id_supplier 
          WHERE tanggal_restock BETWEEN ? AND ?";
$stmt = $config->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $restock_data[] = $row;
}
$stmt->close();

// Calculate total values
$total_jumlah = array_sum(array_column($restock_data, 'jumlah'));
$total_harga = array_sum(array_column($restock_data, 'harga_total'));

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('AdaAllshop');
$pdf->SetTitle('Laporan Pembelian');
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

// Header
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'AdaAllshop Jember', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Perum Demang Mulya Jl Letjen. Suprapto XVIII, Kec. Sumbersari, Kabupaten Jember', 0, 1, 'C');
$pdf->Cell(0, 5, 'HP. 089675330202', 0, 1, 'C');
$pdf->Ln(5);
$pdf->Cell(0, 0, '', 'T', 1, 'C'); // Line separator

// Subheader
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'LAPORAN PEMBELIAN', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, "Periode: $start_date s/d $end_date", 0, 1, 'C');
$pdf->Ln(5);

// Table header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Nama Supplier', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Nama Barang', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'Jumlah', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Harga Beli', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Total Harga', 1, 1, 'C', true);

// Table content
$pdf->SetFont('helvetica', '', 10);
foreach ($restock_data as $index => $restock) {
    $pdf->Cell(10, 8, $index + 1, 1, 0, 'C');
    $pdf->Cell(30, 8, date('Y-m-d', strtotime($restock['tanggal_restock'])), 1, 0, 'C');
    $pdf->Cell(35, 8, $restock['nama_supplier'], 1, 0, 'C');
    $pdf->Cell(50, 8, $restock['nama_barang'], 1, 0, 'C');
    $pdf->Cell(15, 8, $restock['jumlah'], 1, 0, 'C');
    $pdf->Cell(25, 8, 'Rp ' . number_format($restock['harga_beli'], 0, ',', '.'), 1, 0, 'C');
    $pdf->Cell(25, 8, 'Rp ' . number_format($restock['harga_total'], 0, ',', '.'), 1, 1, 'C');
}

// Summary row
$pdf->SetFont('helvetica', 'B', 10);
// Menyesuaikan kolom total agar sejajar dengan tabel
$pdf->Cell(125, 8, 'Total', 1, 0, 'R', true); 
$pdf->Cell(15, 8, $total_jumlah, 1, 0, 'C', true);
$pdf->Cell(25, 8, '-', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Rp ' . number_format($total_harga, 0, ',', '.'), 1, 1, 'C', true);

// Footer
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 10, 'Dicetak pada: ' . date('Y-m-d'), 0, 0, 'R');

$pdf->Output('Laporan_Pembelian_' . $start_date . '_to_' . $end_date . '.pdf', 'I');
?>
