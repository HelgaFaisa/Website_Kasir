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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Styling dasar */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .report-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .report-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #800000;
        }
        .report-title {
            color: #800000;
            font-size: 26px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            position: relative;
        }
        .report-title i {
            margin-right: 15px;
            color: #800000;
        }
        .filter-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .date-filter {
            display: flex;
            align-items: center;
        }
        .date-filter label {
            margin-right: 10px;
        }
        .date-input {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            width: 180px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        .date-input:focus {
            outline: none;
            border-color: #800000;
            box-shadow: 0 0 5px rgba(128, 0, 0, 0.3);
        }
        .button-container {
            display: flex;
            gap: 15px;
            margin-left: 20px;
        }
        .icon-button-box {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            cursor: pointer;
            color: white;
            font-size: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .show-button-box { 
            background-color: #007bff; 
            box-shadow: 0 3px 6px rgba(0, 123, 255, 0.3);
        }
        .print-button-box { 
            background-color: #28a745; 
            box-shadow: 0 3px 6px rgba(40, 167, 69, 0.3);
        }
        .export-button-box { 
            background-color: #ffc107; 
            box-shadow: 0 3px 6px rgba(255, 193, 7, 0.3);
        }
        .icon-button-box:hover {
            transform: scale(1.1) rotate(3deg);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        th {
            background-color: #800000;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            background-color: #ffffff;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        tbody tr:hover {
            background-color: #f1f3f5;
            transition: background-color 0.3s ease;
        }
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .table-controls select {
            padding: 6px;
            margin: 0 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .search-container {
            display: flex;
            align-items: center;
            position: relative;
        }
        .search-container i {
            position: absolute;
            left: 10px;
            color: #800000;
            z-index: 1;
        }
        .search-container input {
            padding: 8px 8px 8px 35px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            width: 250px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .search-container input:focus {
            outline: none;
            border-color: #800000;
            box-shadow: 0 0 5px rgba(128, 0, 0, 0.3);
        }
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .date-filter {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                width: 100%;
            }
            .button-container {
                margin-left: 0;
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="report-section">
            <div class="report-header">
                <h1 class="report-title"><i class="fas fa-shopping-cart"></i> Laporan Pembelian</h1>
            </div>
            <div class="filter-section">
                <div class="date-filter">
                    <label>Periode Tanggal</label>
                    <input type="date" class="date-input" name="start_date" value="<?= $start_date ?>">
                    s/d
                    <input type="date" class="date-input" name="end_date" value="<?= $end_date ?>">
                    <div class="button-container">
                        <div class="icon-button-box show-button-box" onclick="filterData()" title="Tampilkan">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="icon-button-box print-button-box" onclick="printPDF()" title="Cetak Laporan">
                            <i class="fas fa-print"></i>
                        </div>
                        <div class="icon-button-box export-button-box" onclick="exportExcel()" title="Export ke Excel">
                            <i class="fas fa-file-excel"></i>
                        </div>
                    </div>
                </div>
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
                <div class="search-container"> 
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Cari data..." onkeyup="searchTable(this.value)">
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
                                <td><?= htmlspecialchars($restock['tanggal_restock']) ?></td>
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
                        <td>${escapeHtml(restock.tanggal_restock)}</td>
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
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query.toLowerCase())) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function changeEntries(value) {
            const rows = document.querySelectorAll('#reportTable tbody tr');
            
            // Ubah nilai menjadi angka
            const limit = parseInt(value, 10);
            
            // Reset semua baris menjadi tersembunyi
            rows.forEach(row => {
                row.style.display = 'none';
            });

            // Tampilkan baris hingga batas yang dipilih
            rows.forEach((row, index) => {
                if (index < limit) {
                    row.style.display = '';
                }
            });
        }
    </script>
</body>
</html>