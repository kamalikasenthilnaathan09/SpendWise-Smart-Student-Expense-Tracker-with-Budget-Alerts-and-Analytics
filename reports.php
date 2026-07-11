<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION['user_id'];

// Filter parameters for report
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Build Query
$sql = "SELECT * FROM transactions WHERE user_id = $user_id";

if ($start_date !== '') {
    $sql .= " AND transaction_date >= '$start_date'";
}
if ($end_date !== '') {
    $sql .= " AND transaction_date <= '$end_date'";
}
if ($type_filter === 'income' || $type_filter === 'expense') {
    $sql .= " AND type = '$type_filter'";
}

$sql .= " ORDER BY transaction_date DESC, id DESC";
$res = mysqli_query($conn, $sql);

// Fetch currency preferences
$currency_symbol = "₹";
$currency = $_SESSION['currency'] ?? 'INR';
if ($currency === 'USD') $currency_symbol = "$";
elseif ($currency === 'EUR') $currency_symbol = "€";
elseif ($currency === 'GBP') $currency_symbol = "£";

// Handle CSV Export Request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SpendWise_Financial_Report_' . date('Ymd') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Column headings
    fputcsv($output, array('Date', 'Title', 'Category', 'Type', 'Amount (' . $currency . ')'));
    
    while ($row = mysqli_fetch_assoc($res)) {
        fputcsv($output, array(
            $row['transaction_date'],
            $row['title'],
            $row['category'],
            ucfirst($row['type']),
            $row['amount']
        ));
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Financial Reports</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .report-controls {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,.04);
            margin-bottom: 25px;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            align-items: flex-end;
        }

        .control-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
        }

        .control-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
            outline: none;
            transition: 0.2s;
        }

        .control-input:focus {
            border-color: #2563eb;
            background: white;
        }

        .btn-action {
            padding: 11px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s;
            text-decoration: none;
        }

        .btn-generate {
            background: #2563eb;
            color: white;
        }

        .btn-generate:hover {
            background: #1d4ed8;
        }

        .btn-print {
            background: #10b981;
            color: white;
        }

        .btn-print:hover {
            background: #059669;
        }

        .btn-csv {
            background: #f59e0b;
            color: white;
        }

        .btn-csv:hover {
            background: #d97706;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: white;
                color: black;
            }

            .sidebar, .top-header, .report-controls, .btn-action, .view-btn, .btn-delete, th:last-child, td:last-child {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                background: white !important;
                padding: 0 !important;
            }

            .content {
                padding: 0 !important;
            }

            .transactions {
                box-shadow: none !important;
                padding: 0 !important;
                margin-top: 0 !important;
            }

            .transactions::before {
                display: none !important;
            }

            table {
                border: 1px solid #ddd;
            }

            th {
                background: #f3f4f6 !important;
                color: black !important;
                border-bottom: 2px solid #ddd !important;
            }

            td {
                border-bottom: 1px solid #ddd !important;
            }

            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 30px;
            }
        }

        .print-header {
            display: none;
        }
    </style>
</head>

<body>

<div class="wrapper">

    <!-- Sidebar include -->
    <?php include("includes/sidebar.php"); ?>

    <div class="main-content">

        <!-- Header include -->
        <?php include("includes/header.php"); ?>

        <div class="content">

            <!-- Print Header only visible on physical printed sheets -->
            <div class="print-header">
                <h1 style="font-size: 28px; margin-bottom: 5px;">SpendWise Financial Statement</h1>
                <p style="color: #6b7280;">Statement Period: <?php 
                    echo ($start_date ? date("d M Y", strtotime($start_date)) : 'Beginning') . ' to ' . ($end_date ? date("d M Y", strtotime($end_date)) : 'Present'); 
                ?></p>
                <hr style="border: 1px solid #eee; margin: 15px 0;">
            </div>

            <!-- Page Title -->
            <div style="margin-bottom: 25px;">
                <h2 style="font-size: 26px; font-weight: 700; color: #111827;">Financial Reports Generator</h2>
                <p style="color: #6b7280; margin-top: 5px;">Export transactions to CSV or print official statements.</p>
            </div>

            <!-- Report Filters & Actions -->
            <div class="report-controls">
                <form method="GET" action="reports.php" id="reportForm">
                    <div class="controls-grid">
                        <div class="control-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="control-input" value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>

                        <div class="control-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="control-input" value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>

                        <div class="control-group">
                            <label for="type">Type</label>
                            <select name="type" id="type" class="control-input">
                                <option value="">All Transactions</option>
                                <option value="expense" <?php echo ($type_filter === 'expense') ? 'selected' : ''; ?>>Expense Only</option>
                                <option value="income" <?php echo ($type_filter === 'income') ? 'selected' : ''; ?>>Income Only</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-action btn-generate">
                            <i class="fa-solid fa-sync"></i> Generate
                        </button>

                        <button type="button" onclick="window.print()" class="btn-action btn-print">
                            <i class="fa-solid fa-print"></i> Print
                        </button>

                        <a href="reports.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&type=<?php echo urlencode($type_filter); ?>&export=csv" class="btn-action btn-csv">
                            <i class="fa-solid fa-file-csv"></i> Export CSV
                        </a>
                    </div>
                </form>
            </div>

            <!-- Report Result Table -->
            <div class="transactions" style="margin-top: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th class="btn-delete-header">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($res) > 0) {
                            while ($row = mysqli_fetch_assoc($res)) {
                                $date = date("d M Y", strtotime($row['transaction_date']));
                                $type_class = ($row['type'] == 'income') ? 'badge-income' : 'badge-expense';
                                $amount_class = ($row['type'] == 'income') ? 'text-success' : 'text-danger';
                                $amount_prefix = ($row['type'] == 'income') ? '+' : '-';
                                
                                echo "<tr>
                                    <td>{$date}</td>
                                    <td><span style='background: #f3f4f6; padding: 6px 12px; border-radius: 8px; font-weight: 500; color: #111827;'>{$row['category']}</span></td>
                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                    <td><span class='badge {$type_class}'>{$row['type']}</span></td>
                                    <td class='{$amount_class}' style='font-weight: 600;'>{$amount_prefix}{$currency_symbol}" . number_format($row['amount'], 2) . "</td>
                                    <td>
                                        <a href='delete.php?id={$row['id']}' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this transaction?\")' title='Delete'>
                                            <i class='fa-solid fa-trash-can'></i>
                                        </a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align: center; color: #6b7280; padding: 40px;'>No data matches your report criteria.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div> <!-- /content -->

    </div> <!-- /main-content -->

</div> <!-- /wrapper -->

<script>
    // Header search bar hooks redirection
    const headerSearchBar = document.getElementById("searchBar");
    headerSearchBar.onkeypress = function(e) {
        if (e.key === 'Enter') {
            window.location.href = 'transactions.php?search=' + encodeURIComponent(headerSearchBar.value);
        }
    }
</script>

</body>
</html>
