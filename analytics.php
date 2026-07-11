<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION['user_id'];
$theme = $_SESSION['theme'] ?? 'light';

// Currency Setup
$currency_symbol = "₹";
$currency = $_SESSION['currency'] ?? 'INR';
if ($currency === 'USD') $currency_symbol = "$";
elseif ($currency === 'EUR') $currency_symbol = "€";
elseif ($currency === 'GBP') $currency_symbol = "£";

// 1. Fetch category wise expenses
$category_query = mysqli_query($conn, "SELECT category, SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense' GROUP BY category");
$categories = [];
$category_totals = [];
while ($row = mysqli_fetch_assoc($category_query)) {
    $categories[] = $row['category'];
    $category_totals[] = floatval($row['total']);
}

// 2. Fetch income vs expense monthly trend (last 6 months)
$trend_query = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(transaction_date, '%b %Y') AS month,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income_total,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense_total
    FROM transactions 
    WHERE user_id = $user_id 
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY MIN(transaction_date) ASC
    LIMIT 6
");

$months = [];
$monthly_income = [];
$monthly_expense = [];
while ($row = mysqli_fetch_assoc($trend_query)) {
    $months[] = $row['month'];
    $monthly_income[] = floatval($row['income_total']);
    $monthly_expense[] = floatval($row['expense_total']);
}

// 3. High level statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        AVG(CASE WHEN type = 'expense' THEN amount ELSE NULL END) AS avg_expense,
        MAX(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS max_expense
    FROM transactions 
    WHERE user_id = $user_id
");
$stats = mysqli_fetch_assoc($stats_query);
$avg_expense = $stats['avg_expense'] ?? 0;
$max_expense = $stats['max_expense'] ?? 0;

// 4. Monthly comparison (Current Month vs Last Month)
$cur_month_exp_q = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
$cur_month_exp = mysqli_fetch_assoc($cur_month_exp_q)['total'] ?? 0;

$last_month_exp_q = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense' AND MONTH(transaction_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(transaction_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$last_month_exp = mysqli_fetch_assoc($last_month_exp_q)['total'] ?? 0;

$monthly_comparison_percent = 0;
if ($last_month_exp > 0) {
    $monthly_comparison_percent = round((($cur_month_exp - $last_month_exp) / $last_month_exp) * 100, 1);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise Analytics</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,.06);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        body.dark-theme .chart-card {
            background: #1e293b;
            border-color: #334155;
        }

        .chart-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
            align-self: flex-start;
        }

        body.dark-theme .chart-card h3 {
            color: #f8fafc;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 320px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.04);
            border-left: 5px solid #2563eb;
        }

        body.dark-theme .stat-box {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 5px 15px rgba(0,0,0,.2);
        }

        .stat-box.peak {
            border-left-color: #ef4444;
        }

        .stat-box.comparison {
            border-left-color: #f59e0b;
        }

        .stat-box h4 {
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        body.dark-theme .stat-box h4 {
            color: #94a3b8;
        }

        .stat-box p {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        body.dark-theme .stat-box p {
            color: #f8fafc;
        }
    </style>
</head>

<body class="<?php echo ($theme === 'dark') ? 'dark-theme' : ''; ?>">

<div class="wrapper">

    <!-- Sidebar include -->
    <?php include("includes/sidebar.php"); ?>

    <div class="main-content">

        <!-- Header include -->
        <?php include("includes/header.php"); ?>

        <div class="content">

            <!-- Title Header -->
            <div style="margin-bottom: 25px;">
                <h2 style="font-size: 26px; font-weight: 700; color: #111827;">Expense & Income Insights</h2>
                <p style="color: #6b7280; margin-top: 5px;">Visualise your financial data with category and monthly analysis.</p>
            </div>

            <!-- Stats Bar -->
            <div class="stats-summary">
                <div class="stat-box">
                    <h4>Average Expense / Transaction</h4>
                    <p><?php echo $currency_symbol . number_format($avg_expense, 2); ?></p>
                </div>
                <div class="stat-box peak">
                    <h4>Highest Single Expense</h4>
                    <p><?php echo $currency_symbol . number_format($max_expense, 2); ?></p>
                </div>
                <div class="stat-box comparison">
                    <h4>Monthly Trend (vs Last Month)</h4>
                    <p style="color: <?php echo ($monthly_comparison_percent > 0) ? '#ef4444' : '#10b981'; ?>;">
                        <?php echo ($monthly_comparison_percent > 0) ? '+' : ''; ?><?php echo $monthly_comparison_percent; ?>%
                    </p>
                </div>
            </div>

            <!-- Analytics Charts Grid -->
            <div class="analytics-grid">

                <!-- Category Expenses Chart -->
                <div class="chart-card">
                    <h3>Expense Breakdown by Category</h3>
                    <div class="chart-container">
                        <?php if (count($categories) > 0): ?>
                            <canvas id="categoryChart"></canvas>
                        <?php else: ?>
                            <p style="color: #6b7280;">No expense data available to display.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Monthly Trend Chart -->
                <div class="chart-card">
                    <h3>Income vs Expense Monthly Trend</h3>
                    <div class="chart-container">
                        <?php if (count($months) > 0): ?>
                            <canvas id="trendChart"></canvas>
                        <?php else: ?>
                            <p style="color: #6b7280;">No monthly trend data available.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div> <!-- /content -->

    </div> <!-- /main-content -->

</div> <!-- /wrapper -->

<script>
    const activeTheme = "<?php echo $theme; ?>";
    const gridColor = activeTheme === 'dark' ? 'rgba(255, 255, 255, 0.08)' : '#f3f4f6';
    const textColor = activeTheme === 'dark' ? '#cbd5e1' : '#6b7280';

<?php if (count($categories) > 0): ?>
    // Category Pie Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($categories); ?>,
            datasets: [{
                data: <?php echo json_encode($category_totals); ?>,
                backgroundColor: [
                    '#3b82f6', // blue
                    '#ef4444', // red
                    '#f59e0b', // amber
                    '#10b981', // emerald
                    '#8b5cf6', // violet
                    '#ec4899', // pink
                    '#06b6d4', // cyan
                    '#6b7280'  // grey
                ],
                borderWidth: 2,
                borderColor: activeTheme === 'dark' ? '#1e293b' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        color: textColor,
                        font: { family: 'Poppins', size: 12 }
                    }
                }
            },
            cutout: '65%'
        }
    });
<?php endif; ?>

<?php if (count($months) > 0): ?>
    // Trend Bar Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Income',
                    data: <?php echo json_encode($monthly_income); ?>,
                    backgroundColor: 'rgba(22, 163, 74, 0.85)',
                    borderRadius: 6,
                    borderWidth: 0
                },
                {
                    label: 'Expense',
                    data: <?php echo json_encode($monthly_expense); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderRadius: 6,
                    borderWidth: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        color: textColor,
                        font: { family: 'Poppins', size: 12 }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor }
                },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        callback: function(value) { return '<?php echo $currency_symbol; ?>' + value; }
                    }
                }
            }
        }
    });
<?php endif; ?>

    // Global Search Header Hook
    const headerSearchBar = document.getElementById("searchBar");
    headerSearchBar.onkeypress = function(e) {
        if (e.key === 'Enter') {
            window.location.href = 'transactions.php?search=' + encodeURIComponent(headerSearchBar.value);
        }
    }
</script>

</body>
</html>
