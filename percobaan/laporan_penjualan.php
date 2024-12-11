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
        .page-title {
            color: #2c3e50;
            font-size: 26px;
            font-weight: bold;
            margin: 0;
            padding: 20px 0;
            border-bottom: 2px solid #800000;
        }
        .report-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
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
        .filter-section label {
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
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.report-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #800000;
    }
    .report-title {
        color: #800000; /* Dark red color */
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
    
    /* Improve search container styling */
    .search-container {
        display: flex;
        align-items: center;
        position: relative;
    }
    .search-container i {
        position: absolute;
        left: 10px;
        color: #aaa;
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
    /* Improve table controls */
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
     /* Responsive adjustments */
     @media (max-width: 768px) {
        .filter-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        .button-container {
            width: 100%;
            justify-content: space-between;
        }
    }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <div class="main-content">
    <!-- <h1 class="page-title">
            <i class="fas fa-chart-line"></i>
            Laporan Penjualan
        </h1> -->
        <div class="report-section">
        <div class="report-header">
            <h1 class="report-title"><i class="fas fa-chart-line"></i> Laporan Penjualan</h1>
        </div>
            <div class="filter-section">
                <div>
                    <label>Periode Tanggal</label>
                    <input type="date" class="date-input" name="start_date" value="<?= $start_date ?>">
                    s/d
                    <input type="date" class="date-input" name="end_date" value="<?= $end_date ?>">
                </div>
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
    let visibleCount = 0;
    
    rows.forEach((row, index) => {
        if (row.style.display !== 'none') {
            if (visibleCount < value) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    });
}
    </script>
</body>
</html>
