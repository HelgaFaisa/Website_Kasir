<?php
// laporan_penjualan.php
session_start();

// Date filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

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

// Handle AJAX request for filtered data
if (isset($_GET['ajax'])) {
    echo json_encode($transactions);
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

        .show-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .print-button {
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

        .pagination {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .pagination-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .pagination-button.active {
            background-color: #0056b3;
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
                <button class="print-button" onclick="printReport()">Cetak Laporan</button>
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
                        <th>Tanggal</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Kasir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $index => $transaction): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= date('Y-m-d', strtotime($transaction['tanggal'] ?? date('Y-m-d'))) ?></td>
                                <td><?= htmlspecialchars($transaction['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($transaction['jumlah']) ?></td>
                                <td>Rp <?= number_format($transaction['total'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($transaction['kasir']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No data available in table</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="pagination">
                <button class="pagination-button">Previous</button>
                <button class="pagination-button active">1</button>
                <button class="pagination-button">Next</button>
            </div>
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
                data.forEach((transaction, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${transaction.tanggal || new Date().toISOString().split('T')[0]}</td>
                        <td>${escapeHtml(transaction.nama_barang)}</td>
                        <td>${escapeHtml(transaction.jumlah)}</td>
                        <td>Rp ${numberFormat(transaction.total)}</td>
                        <td>${escapeHtml(transaction.kasir)}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No data available in table</td></tr>';
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

        function printReport() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            window.open(`cetak_laporan.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
        }

        function searchTable(query) {
            const rows = document.querySelectorAll('#reportTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query.toLowerCase()) ? '' : 'none';
            });
        }

        function changeEntries(value) {
            // Implement pagination logic here
            console.log('Showing', value, 'entries per page');
        }
    </script>
</body>
</html>

<?php
// cetak_laporan.php
