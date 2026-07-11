<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");
include_once("includes/functions.php");

$user_id = $_SESSION['user_id'];

// Get theme and settings from session
$theme = $_SESSION['theme'] ?? 'light';
$currency_symbol = "₹";
$currency = $_SESSION['currency'] ?? 'INR';
if ($currency === 'USD') $currency_symbol = "$";
elseif ($currency === 'EUR') $currency_symbol = "€";
elseif ($currency === 'GBP') $currency_symbol = "£";

$monthlyBudgetLimit = $_SESSION['monthly_budget'] ?? 50000.00;
$goalName = $_SESSION['savings_goal_name'] ?? 'New Laptop';
$goalTarget = $_SESSION['savings_goal_target'] ?? 50000.00;
$goalCurrent = $_SESSION['savings_goal_current'] ?? 33433.00;

// 1. Fetch Summary Card Metrics & Counts
// Income Metrics
$income_query = mysqli_query($conn, "SELECT SUM(amount) AS total, COUNT(*) AS count FROM transactions WHERE user_id = $user_id AND type = 'income'");
$income_row = mysqli_fetch_assoc($income_query);
$totalIncome = $income_row['total'] ?? 0;
$incomeCount = $income_row['count'] ?? 0;

// Expense Metrics
$expense_query = mysqli_query($conn, "SELECT SUM(amount) AS total, COUNT(*) AS count FROM transactions WHERE user_id = $user_id AND type = 'expense'");
$expense_row = mysqli_fetch_assoc($expense_query);
$totalExpense = $expense_row['total'] ?? 0;
$expenseCount = $expense_row['count'] ?? 0;

// Balance & Savings Calculations
$totalBalance = $totalIncome - $totalExpense;
$savingsRate = ($totalIncome > 0) ? round(($totalBalance / $totalIncome) * 100, 1) : 0;

// 2. Fetch Quick Insights Card Metrics
// Today's Expense
$today_expense_query = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense' AND transaction_date = CURDATE()");
$today_expense_row = mysqli_fetch_assoc($today_expense_query);
$todayExpense = $today_expense_row['total'] ?? 0;

// Total Transactions
$total_trans_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM transactions WHERE user_id = $user_id");
$total_trans_row = mysqli_fetch_assoc($total_trans_query);
$totalTransactionsCount = $total_trans_row['count'] ?? 0;

// Highest Spending Category
$highest_cat_query = mysqli_query($conn, "SELECT category, SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense' GROUP BY category ORDER BY total DESC LIMIT 1");
$highest_cat_row = mysqli_fetch_assoc($highest_cat_query);
$highestSpendingCategory = $highest_cat_row['category'] ?? 'None';
$highestSpendingAmount = $highest_cat_row['total'] ?? 0;

// Monthly Budget Usage (Current Month spend vs limit)
$monthly_expense_query = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
$monthly_expense_row = mysqli_fetch_assoc($monthly_expense_query);
$currentMonthExpense = $monthly_expense_row['total'] ?? 0;
$budgetUsageRate = ($monthlyBudgetLimit > 0) ? round(($currentMonthExpense / $monthlyBudgetLimit) * 100, 1) : 0;

// Goal progress calculations
$goalProgressRate = ($goalTarget > 0) ? round(($goalCurrent / $goalTarget) * 100, 1) : 0;

// 3. Generate Smart Financial Insights dynamically
$insights = [];
if ($highestSpendingCategory !== 'None') {
    $insights[] = "💡 <b>" . htmlspecialchars($highestSpendingCategory) . "</b> is your highest spending category (" . $currency_symbol . number_format($highestSpendingAmount, 0) . " total).";
}
if ($savingsRate > 0) {
    $insights[] = "📈 Excellent! You saved <b>" . $savingsRate . "%</b> of your cumulative income.";
} else {
    $insights[] = "⚠️ Attention: Your cumulative expenses exceed your income. Focus on budget cuts.";
}
if ($totalBalance > 0) {
    $insights[] = "💸 Your net income exceeded overall expenses by <b>" . $currency_symbol . number_format($totalBalance, 2) . "</b>.";
}
// Zero spending categories this month
$check_cats = ['Food', 'Travel', 'Shopping', 'Entertainment', 'Utilities'];
$spent_cats_q = mysqli_query($conn, "SELECT DISTINCT category FROM transactions WHERE user_id = $user_id AND type = 'expense' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
$spent_cats = [];
while ($row = mysqli_fetch_assoc($spent_cats_q)) {
    $spent_cats[] = $row['category'];
}
$zero_cats = array_diff($check_cats, $spent_cats);
if (!empty($zero_cats)) {
    $insights[] = "🛡️ Great job! No expenses recorded in <b>" . htmlspecialchars(implode(', ', $zero_cats)) . "</b> this month.";
} else {
    $insights[] = "📊 Pro-tip: Try setting a zero-spending day for elective categories to save more.";
}

// 4. Spending Heatmap Data (Current Month)
$days_in_month = date('t');
$daily_exp_q = mysqli_query($conn, "
    SELECT DAY(transaction_date) AS day, SUM(amount) AS total 
    FROM transactions 
    WHERE user_id = $user_id 
      AND type = 'expense' 
      AND MONTH(transaction_date) = MONTH(CURDATE()) 
      AND YEAR(transaction_date) = YEAR(CURDATE()) 
    GROUP BY DAY(transaction_date)
");
$daily_spending = array_fill(1, $days_in_month, 0);
while ($row = mysqli_fetch_assoc($daily_exp_q)) {
    $daily_spending[$row['day']] = floatval($row['total']);
}
$first_day_timestamp = strtotime(date('Y-m-01'));
$first_day_of_week = date('w', $first_day_timestamp); // 0 (Sunday) to 6 (Saturday)

// 5. Activity Timeline Data (Latest 5 actions only)
$timeline_q = mysqli_query($conn, "SELECT title, type, category, amount, transaction_date FROM transactions WHERE user_id = $user_id ORDER BY id DESC LIMIT 5");
$timeline_items = [];
while ($row = mysqli_fetch_assoc($timeline_q)) {
    $timeline_items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise Premium Dashboard</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <style>
        /* Progress loader keyframes */
        @keyframes animateWidthSavings {
            from { width: 0%; }
            to { width: <?php echo max(0, min(100, $savingsRate)); ?>%; }
        }
        @keyframes animateWidthBudget {
            from { width: 0%; }
            to { width: <?php echo max(0, min(100, $budgetUsageRate)); ?>%; }
        }
        @keyframes animateWidthGoal {
            from { width: 0%; }
            to { width: <?php echo max(0, min(100, $goalProgressRate)); ?>%; }
        }

        .savings-progress-bar {
            animation: animateWidthSavings 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
        }
        .budget-progress-bar {
            animation: animateWidthBudget 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
        }
        .goal-progress-bar {
            animation: animateWidthGoal 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
        }

        /* Budget banner colors */
        .budget-banner {
            padding: 15px 25px;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: fadeInUp 0.4s ease-out;
        }
        .budget-banner.warning {
            background-color: #fef3c7;
            color: #b45309;
            border-left: 5px solid #d97706;
        }
        .budget-banner.danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 5px solid #ef4444;
        }
        body.dark-theme .budget-banner.warning {
            background-color: rgba(217, 119, 6, 0.15);
            color: #f59e0b;
        }
        body.dark-theme .budget-banner.danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        /* Heatmap Grid labels */
        .heatmap-grid-labels {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 5px;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
        }

        /* Smart Insights Card styling */
        .insights-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,.04);
            border: 1px solid #f3f4f6;
            height: 100%;
        }
        body.dark-theme .insights-card {
            background: #1e293b;
            border-color: #334155;
        }
        .insights-list {
            margin-top: 15px;
        }
        .insight-item {
            padding: 12px 15px;
            background: #f9fafb;
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #4b5563;
            border-left: 3px solid #2563eb;
            line-height: 1.4;
        }
        body.dark-theme .insight-item {
            background: #0f172a;
            color: #cbd5e1;
            border-left-color: #2563eb;
        }
    </style>
</head>

<body class="<?php echo ($theme === 'dark') ? 'dark-theme' : ''; ?>">

<div class="wrapper">

    <!-- Sidebar include (contains global FAB & Modal) -->
    <?php include("includes/sidebar.php"); ?>

    <div class="main-content">

        <!-- Header include (contains notifications dropdown & theme toggler) -->
        <?php include("includes/header.php"); ?>

        <div class="content">

            <!-- Skeleton Shimmer Loader -->
            <div id="shimmerOverlay" style="position: fixed; top: 0; left: 260px; right: 0; bottom: 0; background: inherit; z-index: 100; padding: 35px; display: flex; flex-direction: column; gap: 25px; transition: opacity 0.3s ease;">
                <div style="display: flex; gap: 20px;">
                    <div class="skeleton" style="flex: 1; height: 130px; border-radius: 18px;"></div>
                    <div class="skeleton" style="flex: 1; height: 130px; border-radius: 18px;"></div>
                    <div class="skeleton" style="flex: 1; height: 130px; border-radius: 18px;"></div>
                    <div class="skeleton" style="flex: 1; height: 130px; border-radius: 18px;"></div>
                </div>
                <div style="display: flex; gap: 25px; flex: 1;">
                    <div class="skeleton" style="flex: 2; border-radius: 18px; height: 300px;"></div>
                    <div class="skeleton" style="flex: 1; border-radius: 18px; height: 300px;"></div>
                </div>
            </div>

            <!-- Hydrated Dashboard Layout Content -->
            <div id="dashboardContent" class="skeleton-hide">

                <!-- Summary Cards Row -->
                <div class="cards">
                    <!-- Balance -->
                    <div class="card balance">
                        <div>
                            <h3>Current Balance</h3>
                            <h2><?php echo $currency_symbol . number_format($totalBalance, 2); ?></h2>
                            <p><?php echo ($totalBalance >= 0) ? 'Positive Budget' : 'Budget Deficit'; ?></p>
                        </div>
                        <div class="icon">💰</div>
                    </div>

                    <!-- Expense -->
                    <div class="card expense">
                        <div>
                            <h3>Total Expense</h3>
                            <h2><?php echo $currency_symbol . number_format($totalExpense, 2); ?></h2>
                            <p><?php echo $expenseCount; ?> Expense transactions</p>
                        </div>
                        <div class="icon">💸</div>
                    </div>

                    <!-- Income -->
                    <div class="card income">
                        <div>
                            <h3>Total Income</h3>
                            <h2><?php echo $currency_symbol . number_format($totalIncome, 2); ?></h2>
                            <p><?php echo $incomeCount; ?> Income transactions</p>
                        </div>
                        <div class="icon">📈</div>
                    </div>

                    <!-- Savings with Animated Progress -->
                    <div class="card savings">
                        <div style="width: 100%;">
                            <h3>Savings</h3>
                            <h2><?php echo $currency_symbol . number_format(max(0, $totalBalance), 2); ?></h2>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                <span style="font-size: 13px; font-weight: 500;">Savings Rate</span>
                                <span style="font-size: 13px; font-weight: 600;"><?php echo $savingsRate; ?>%</span>
                            </div>
                            <div class="savings-progress-container">
                                <div class="savings-progress-bar"></div>
                            </div>
                        </div>
                        <div class="icon" style="margin-left: 15px;">🏦</div>
                    </div>
                </div>

                <!-- Budget Warning Alerts -->
                <?php if ($budgetUsageRate >= 100): ?>
                    <div class="budget-banner danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>🚨 Budget exceeded! You have spent <?php echo $currency_symbol . number_format($currentMonthExpense, 2); ?> of your <?php echo $currency_symbol . number_format($monthlyBudgetLimit, 2); ?> monthly limit.</span>
                    </div>
                <?php elseif ($budgetUsageRate >= 80): ?>
                    <div class="budget-banner warning">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>⚠️ Approaching your monthly budget threshold (<?php echo $budgetUsageRate; ?>% utilized).</span>
                    </div>
                <?php endif; ?>

                <!-- Row 2: Insights Grid & Goal Widget -->
                <div class="insights-goal-section">
                    <!-- Insights Grid -->
                    <div class="insights-grid">
                        <div class="insight-card expense-box">
                            <div class="insight-icon"><i class="fa-solid fa-calendar-day"></i></div>
                            <div class="insight-info">
                                <h4>Today's Expense</h4>
                                <p><?php echo $currency_symbol . number_format($todayExpense, 2); ?></p>
                            </div>
                        </div>

                        <div class="insight-card transaction-box">
                            <div class="insight-icon"><i class="fa-solid fa-list-check"></i></div>
                            <div class="insight-info">
                                <h4>Total Transactions</h4>
                                <p><?php echo $totalTransactionsCount; ?></p>
                            </div>
                        </div>

                        <div class="insight-card category-box">
                            <div class="insight-icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                            <div class="insight-info">
                                <h4>Top Category</h4>
                                <p><?php echo htmlspecialchars($highestSpendingCategory); ?> (<?php echo $currency_symbol . number_format($highestSpendingAmount, 0); ?>)</p>
                            </div>
                        </div>

                        <div class="insight-card budget-box">
                            <div class="insight-icon"><i class="fa-solid fa-sliders"></i></div>
                            <div class="insight-info" style="width: calc(100% - 63px);">
                                <h4>Budget Usage</h4>
                                <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; color: #111827;">
                                    <span><?php echo $currency_symbol . number_format($currentMonthExpense, 0); ?> / <?php echo $currency_symbol . number_format($monthlyBudgetLimit, 0); ?></span>
                                    <span style="font-size: 12px; margin-top: 2px;"><?php echo $budgetUsageRate; ?>%</span>
                                </div>
                                <div class="budget-progress-container" style="background: <?php echo ($budgetUsageRate >= 100) ? '#fee2e2' : (($budgetUsageRate >= 80) ? '#fef3c7' : '#e5e7eb'); ?>;">
                                    <div class="budget-progress-bar" style="background: <?php echo ($budgetUsageRate >= 100) ? '#ef4444' : (($budgetUsageRate >= 80) ? '#f59e0b' : '#10b981'); ?>;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Goal Widget -->
                    <div class="goal-card-container">
                        <div class="goal-card">
                            <div class="goal-header">
                                <h3>Savings Goal</h3>
                                <span>Active</span>
                            </div>
                            <div>
                                <div class="goal-title"><i class="fa-solid fa-laptop text-success" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($goalName); ?></div>
                                <div class="goal-progress-wrapper">
                                    <div class="goal-values">
                                        <span><?php echo $currency_symbol . number_format($goalCurrent, 2); ?></span>
                                        <span>Target: <?php echo $currency_symbol . number_format($goalTarget, 2); ?></span>
                                    </div>
                                    <div class="goal-progress-container">
                                        <div class="goal-progress-bar"></div>
                                    </div>
                                    <div style="text-align: right; font-size: 12px; color: #6b7280; font-weight: 600; margin-top: 6px;">
                                        <?php echo $goalProgressRate; ?>% Reached
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Monthly Spending Heatmap & Smart Insights -->
                <div class="insights-goal-section" style="margin-top: 30px;">
                    <!-- Heatmap Grid -->
                    <div class="heatmap-container" style="grid-column: span 7; margin-top: 0; height: 100%;">
                        <div class="heatmap-header">
                            <h3>Monthly Spending Heatmap (<?php echo date("F Y"); ?>)</h3>
                            <div class="heatmap-legend">
                                <span>Less</span>
                                <div class="legend-square day-level-0"></div>
                                <div class="legend-square day-level-1"></div>
                                <div class="legend-square day-level-2"></div>
                                <div class="legend-square day-level-3"></div>
                                <div class="legend-square day-level-4"></div>
                                <span>More</span>
                            </div>
                        </div>
                        
                        <div class="heatmap-grid-labels">
                            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                        </div>
                        <div class="heatmap-grid">
                            <?php for ($i = 0; $i < $first_day_of_week; $i++): ?>
                                <div style="aspect-ratio: 1;"></div>
                            <?php endfor; ?>

                            <?php 
                            for ($day = 1; $day <= $days_in_month; $day++): 
                                $total_spent = $daily_spending[$day];
                                $level = 0;
                                if ($total_spent > 5000) $level = 4;
                                elseif ($total_spent > 2000) $level = 3;
                                elseif ($total_spent > 500) $level = 2;
                                elseif ($total_spent > 0) $level = 1;
                            ?>
                                <div class="heatmap-day day-level-<?php echo $level; ?>">
                                    <?php echo $day; ?>
                                    <div class="tooltip">
                                        <?php echo date("d M Y", strtotime(date("Y-m-") . $day)); ?><br>
                                        <b><?php echo $currency_symbol . number_format($total_spent, 2); ?> spent</b>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Smart Insights Card -->
                    <div class="insights-card" style="grid-column: span 5; height: 100%;">
                        <h3 style="font-size: 16px; font-weight: 600; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 10px; margin-bottom: 15px;">Smart Financial Insights</h3>
                        <div class="insights-list">
                            <?php foreach ($insights as $insight): ?>
                                <div class="insight-item">
                                    <?php echo $insight; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Row 4: Recent Activity Timeline & Recent Transactions Table -->
                <div class="insights-goal-section" style="margin-top: 30px; margin-bottom: 20px;">
                    <!-- Activity Timeline (Last 5 records only) -->
                    <div class="timeline-card" style="grid-column: span 4; margin-top: 0; height: 100%;">
                        <h3>Recent Activity Timeline</h3>
                        <div class="timeline-wrapper">
                            <?php if (count($timeline_items) > 0): ?>
                                <?php foreach ($timeline_items as $item): 
                                    $is_income = ($item['type'] == 'income');
                                    $bullet_class = $is_income ? 'income-bullet' : 'expense-bullet';
                                    $val_class = $is_income ? 'text-success' : 'text-danger';
                                    $val_prefix = $is_income ? '+' : '-';
                                    $icon_emoji = "🛍️";
                                    
                                    if ($item['category'] == 'Food') $icon_emoji = "🍔";
                                    elseif ($item['category'] == 'Travel') $icon_emoji = "🚗";
                                    elseif ($item['category'] == 'Salary') $icon_emoji = "💰";
                                    elseif ($item['category'] == 'Investment') $icon_emoji = "📈";
                                    elseif ($item['category'] == 'Entertainment') $icon_emoji = "🍿";
                                    elseif ($item['category'] == 'Utilities') $icon_emoji = "🔌";
                                ?>
                                    <div class="timeline-item">
                                        <div class="timeline-bullet <?php echo $bullet_class; ?>"></div>
                                        <div class="timeline-info">
                                            <div class="timeline-details">
                                                <h4><?php echo $icon_emoji . " " . htmlspecialchars($item['title']); ?></h4>
                                                <span><?php echo date("d M Y", strtotime($item['transaction_date'])); ?></span>
                                            </div>
                                            <span class="timeline-value <?php echo $val_class; ?>">
                                                <?php echo $val_prefix . $currency_symbol . number_format($item['amount'], 0); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #6b7280; font-size: 13px; text-align: center; padding: 20px;">No recent transactions logged.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Transactions Table (Last 5 records only with View All button) -->
                    <div class="transactions" style="grid-column: span 8; margin-top: 0; height: 100%;">
                        <div class="section-header">
                            <h3>Recent Ledger Transactions</h3>
                            <a href="transactions.php" class="view-btn" style="text-decoration: none; text-align: center; display: inline-block;">
                                View All
                            </a>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="transactionTableBody">
                                <?php
                                $recent_res = mysqli_query($conn, "SELECT * FROM transactions WHERE user_id = $user_id ORDER BY transaction_date DESC, id DESC LIMIT 5");
                                if (mysqli_num_rows($recent_res) > 0) {
                                    while ($row = mysqli_fetch_assoc($recent_res)) {
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
                                    echo "<tr><td colspan='6' style='text-align: center; color: #6b7280; padding: 30px;'>No transactions found. Click '+' to start tracking!</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> <!-- /dashboardContent -->

        </div> <!-- /content -->

    </div> <!-- /main-content -->

</div> <!-- /wrapper -->

<script>
    // 1. Shimmer Loader Fadeout
    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            const overlay = document.getElementById("shimmerOverlay");
            const dashboard = document.getElementById("dashboardContent");
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.display = 'none';
                dashboard.classList.add("skeleton-show");
            }, 300);
        }, 400);
    });

    // 2. Local Search Bar Filtering
    const searchBar = document.getElementById("searchBar");
    searchBar.onkeyup = function() {
        const query = searchBar.value.toLowerCase();
        const rows = document.querySelectorAll("#transactionTableBody tr");
        
        rows.forEach(row => {
            if (row.cells.length > 1) {
                const category = row.cells[1].textContent.toLowerCase();
                const title = row.cells[2].textContent.toLowerCase();
                if (category.includes(query) || title.includes(query)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        });
    }
</script>

</body>
</html>