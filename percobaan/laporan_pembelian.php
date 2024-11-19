<?php
// laporan_pembelian.php
session_start();
require_once 'config.php';

// Date filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get restock data from database
$restock_data = [];
$query = "SELECT r.*, s.nama_supplier 
          FROM restock r 
          LEFT JOIN supplier s ON r.id_supplier = s.id_supplier 
          WHERE tanggal_restock BETWEEN ? AND ?";
$stmt = $config->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $restock_data[] = $row;
}
$stmt->close();

// Handle AJAX request for filtered data
if (isset($_GET['ajax'])) {
    echo json_encode($restock_data);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian</title>
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
            padding: 0 20px;
        }

        .page-title {
            color: #800000;
            margin: 0;
            padding: 20px 0;
        }

        .report-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-section {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
        }

        .date-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .show-button, .export-button, .print-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <h1 class="page-title">Laporan Pembelian</h1>

        <div class="report-section">
            <div class="filter-section">
                <div>
                    <label>Periode Tanggal</label>
                    <input type="date" class="date-input" name="start_date" value="<?= $start_date ?>">
                    s/d
                    <input type="date" class="date-input" name="end_date" value="<?= $end_date ?>">
                </div>
                <button class="show-button" onclick="filterData()">Tampilkan</button>
                <button class="print-button" onclick="printPDF()">Cetak Laporan</button>
                <button class="export-button" onclick="exportExcel()">Export ke Excel</button>
            </div>

            <div class="table-controls">
                <div>
                    Show 
                    <select onchange="changeEntries(this.value)">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    entries
                </div>
                <div>
                    Search: <input type="text" onkeyup="searchTable(this.value)">
                </div>
            </div>

            <table id="reportTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Restock</th>
                        <th>Nama Supplier</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Harga Beli</th>
                        <th>Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($restock_data)): ?>
                        <?php foreach ($restock_data as $index => $restock): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= date('Y-m-d', strtotime($restock['tanggal_restock'])) ?></td>
                                <td><?= htmlspecialchars($restock['nama_supplier']) ?></td>
                                <td><?= htmlspecialchars($restock['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($restock['jumlah']) ?></td>
                                <td>Rp <?= number_format($restock['harga_beli'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($restock['harga_total'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Tidak ada data dalam periode ini</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterData() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            
            fetch(`?ajax=1&start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(data => {
                    updateTable(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function updateTable(data) {
            const tbody = document.querySelector('#reportTable tbody');
            tbody.innerHTML = '';

            if (data.length > 0) {
                data.forEach((restock, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${restock.tanggal_restock}</td>
                        <td>${escapeHtml(restock.nama_supplier)}</td>
                        <td>${escapeHtml(restock.nama_barang)}</td>
                        <td>${escapeHtml(restock.jumlah)}</td>
                        <td>Rp ${numberFormat(restock.harga_beli)}</td>
                        <td>Rp ${numberFormat(restock.harga_total)}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Tidak ada data dalam periode ini</td></tr>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function numberFormat(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        function printPDF() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            window.location.href = `cetak_laporanPembelian.php?start_date=${startDate}&end_date=${endDate}`;
        }

        function exportExcel() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            window.location.href = `export_excel.php?start_date=${startDate}&end_date=${endDate}&type=pembelian`;
        }

        function searchTable(query) {
            const rows = document.querySelectorAll('#reportTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query.toLowerCase()) ? '' : 'none';
            });
        }

        function changeEntries(value) {
            console.log('Showing', value, 'entries per page');
            // Implement pagination logic here
        }
    </script>
</body>
</html>