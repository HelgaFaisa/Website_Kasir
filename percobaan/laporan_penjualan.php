<?php
// laporan_penjualan.php
session_start();
require_once 'config.php';

// Date filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get penjualan data from database
$penjualan_data = [];
$query = "SELECT * FROM penjualan WHERE tanggal_input BETWEEN ? AND ?";
$stmt = $config->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $penjualan_data[] = $row;
}
$stmt->close();

// Handle AJAX request for filtered data
if (isset($_GET['ajax'])) {
    echo json_encode($penjualan_data);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
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
        <h1 class="page-title">Laporan Penjualan</h1>

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
                        <th>Invoice</th>
                        <th>Tanggal Penjualan</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th>Kembali</th>
                        <th>Diskon</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($penjualan_data)): ?>
                        <?php foreach ($penjualan_data as $index => $penjualan): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($penjualan['invoice']) ?></td>
                                <td><?= date('Y-m-d', strtotime($penjualan['tanggal_input'])) ?></td>
                                <td>Rp <?= number_format($penjualan['total'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($penjualan['bayar'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($penjualan['kembali'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($penjualan['diskon']) ?>%</td>
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
                data.forEach((penjualan, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${escapeHtml(penjualan.invoice)}</td>
                        <td>${penjualan.tanggal_input}</td>
                        <td>Rp ${numberFormat(penjualan.total)}</td>
                        <td>Rp ${numberFormat(penjualan.bayar)}</td>
                        <td>Rp ${numberFormat(penjualan.kembali)}</td>
                        <td>${penjualan.diskon}%</td>
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
            window.location.href = `cetakPDF_penjualan.php?start_date=${startDate}&end_date=${endDate}`;
        }

        function exportExcel() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            window.location.href = `excelPenjualan.php?start_date=${startDate}&end_date=${endDate}&type=penjualan`;
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
