<?php
// cetak_laporan_pdf.php
require_once 'config.php';
require_once 'TCPDF-main/tcpdf.php';

// Date filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Retrieve sales data
$sales_data = [];
$query = "SELECT * FROM penjualan WHERE tanggal_input BETWEEN ? AND ?";
$stmt = $config->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
}
$stmt->close();

// Calculate total values
$total_penjualan = array_sum(array_column($sales_data, 'total'));
$total_bayar = array_sum(array_column($sales_data, 'bayar'));
$total_kembali = array_sum(array_column($sales_data, 'kembali'));

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('AdaAllshop');
$pdf->SetTitle('Laporan Penjualan');
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
$pdf->Cell(0, 10, 'LAPORAN PENJUALAN', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, "Periode: $start_date s/d $end_date", 0, 1, 'C');
$pdf->Ln(5);

// Table header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Invoice', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Total', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Bayar', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Kembali', 1, 0, 'C', true);
$pdf->Cell(22, 8, 'Diskon', 1, 1, 'C', true);

// Table content
$pdf->SetFont('helvetica', '', 10);
foreach ($sales_data as $index => $sale) {
    $pdf->Cell(10, 8, $index + 1, 1, 0, 'C');
    $pdf->Cell(35, 8, $sale['invoice'], 1, 0, 'C');
    $pdf->Cell(30, 8, date('Y-m-d', strtotime($sale['tanggal_input'])), 1, 0, 'C');
    $pdf->Cell(35, 8, 'Rp ' . number_format($sale['total'], 0, ',', '.'), 1, 0, 'C');
    $pdf->Cell(28, 8, 'Rp ' . number_format($sale['bayar'], 0, ',', '.'), 1, 0, 'C');
    $pdf->Cell(28, 8, 'Rp ' . number_format($sale['kembali'], 0, ',', '.'), 1, 0, 'C');
    $pdf->Cell(22, 8, $sale['diskon'] . '%', 1, 1, 'C');
}

// Summary row
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(160, 8, 'Total', 1, 0, 'R', true);
$pdf->Cell(35, 8, 'Rp ' . number_format($total_penjualan, 0, ',', '.'), 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Rp ' . number_format($total_bayar, 0, ',', '.'), 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Rp ' . number_format($total_kembali, 0, ',', '.'), 1, 1, 'C', true);

// Footer
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 10, 'Dicetak pada: ' . date('Y-m-d'), 0, 0, 'R');

// Output the PDF
$pdf->Output('Laporan_Penjualan_' . $start_date . '_to_' . $end_date . '.pdf', 'I');
?>