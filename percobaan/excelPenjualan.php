<?php
// export_excel.php
session_start();
require_once 'config.php';

// Set header untuk export Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=laporan_" . date('Y-m-d') . ".xls");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);

// Ambil parameter tanggal dari URL atau default
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$type = isset($_GET['type']) ? $_GET['type'] : 'penjualan';

// Custom styling
$style = 'style="border: 1px solid black; padding: 5px;"';
$center_style = 'style="text-align: center; border: 1px solid black; padding: 5px;"';
$right_style = 'style="text-align: right; border: 1px solid black; padding: 5px;"';

// Header section dengan garis bawah
?>
<div style="text-align: center;">
    <h2 style="margin: 0;">AdaAllshop Jember</h2>
    <p style="margin: 5px 0;">Perum Demang Mulya Jl. Letjen. Suprapto XVIII, Kec. Sumbersan, Kabupaten Jember</p>
    <p style="margin: 5px 0;">HP: 089675330202</p>
</div>
<div style="border-bottom: 2px solid black; margin: 10px 0;"></div>
<br>

<div style="text-align: center;">
    <h3 style="margin: 0; text-transform: uppercase;">LAPORAN PENJUALAN</h3>
</div>
<br>

<?php
// Query untuk data penjualan
$query = "SELECT * FROM penjualan WHERE tanggal_input BETWEEN ? AND ?";
$stmt = $config->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
?>
<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th <?= $center_style ?>>No</th>
            <th <?= $center_style ?>>Invoice</th>
            <th <?= $center_style ?>>Tanggal Penjualan</th>
            <th <?= $center_style ?>>Total</th>
            <th <?= $center_style ?>>Bayar</th>
            <th <?= $center_style ?>>Kembali</th>
            <th <?= $center_style ?>>Diskon</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        $total_all = 0;
        while ($row = $result->fetch_assoc()): 
            $total_all += $row['total'];
        ?>
            <tr>
                <td <?= $center_style ?>><?= $no++ ?></td>
                <td <?= $style ?>><?= htmlspecialchars($row['invoice']) ?></td>
                <td <?= $style ?>><?= date('d/m/Y', strtotime($row['tanggal_input'])) ?></td>
                <td <?= $right_style ?>>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                <td <?= $right_style ?>>Rp <?= number_format($row['bayar'], 0, ',', '.') ?></td>
                <td <?= $right_style ?>>Rp <?= number_format($row['kembali'], 0, ',', '.') ?></td>
                <td <?= $right_style ?>><?= htmlspecialchars($row['diskon']) ?>%</td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="6" <?= $right_style ?>>Total</td>
            <td <?= $right_style ?>>Rp <?= number_format($total_all, 0, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<?php
// Calculate summary for sales report
$total_query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(total) as total_amount
                FROM penjualan
                WHERE tanggal_input BETWEEN ? AND ?";
$stmt = $config->prepare($total_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();
?>

<br>
<table style="width: 300px; border-collapse: collapse;">
    <thead>
        <tr>
            <th colspan="2" <?= $center_style ?>>Ringkasan Laporan Penjualan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td <?= $style ?>>Periode</td>
            <td <?= $style ?>><?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></td>
        </tr>
        <tr>
            <td <?= $style ?>>Total Transaksi</td>
            <td <?= $style ?>><?= $totals['total_transactions'] ?></td>
        </tr>
        <tr>
            <td <?= $style ?>>Total Nilai Penjualan</td>
            <td <?= $style ?>>Rp <?= number_format($totals['total_amount'], 0, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<br>
<div style="text-align: right;">
    <p>Dicetak pada: <?= date('d/m/Y') ?></p>
</div>
