<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $transaction_date = $_POST['transaction_date'];
    $type = $_POST['type']; // 'income' or 'expense'

    // Validate type
    if ($type !== 'income' && $type !== 'expense') {
        $type = 'expense';
    }

    if (empty($title) || $amount <= 0 || empty($category) || empty($transaction_date)) {
        echo "Error: Invalid inputs. Please fill all fields properly.";
        exit();
    }

    // Prepared statement
    $stmt = mysqli_prepare($conn, "INSERT INTO transactions (user_id, title, amount, type, category, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isdsss", $user_id, $title, $amount, $type, $category, $transaction_date);

    if (mysqli_stmt_execute($stmt)) {
        // Notification & Flash trigger
        include_once("includes/functions.php");
        
        $is_expense = ($type === 'expense');
        $currency_symbol = "₹";
        $currency = $_SESSION['currency'] ?? 'INR';
        if ($currency === 'USD') $currency_symbol = "$";
        elseif ($currency === 'EUR') $currency_symbol = "€";
        elseif ($currency === 'GBP') $currency_symbol = "£";

        $amount_formatted = $currency_symbol . number_format($amount, 2);
        if ($is_expense) {
            $msg = "Expense added successfully: '" . htmlspecialchars($title) . "' ($amount_formatted).";
            $notif_type = "transaction_added";
        } else {
            $msg = "Income added successfully: '" . htmlspecialchars($title) . "' ($amount_formatted).";
            $notif_type = "transaction_added";
        }

        // Add main transaction notification
        add_notification($conn, $user_id, $msg, $notif_type);
        $_SESSION['flash_notification'] = ['message' => $msg, 'type' => $notif_type];

        // Perform boundary checks for budget alerts or goal accomplishments
        if ($is_expense) {
            $monthly_budget = $_SESSION['monthly_budget'] ?? 50000.00;
            
            // Query total expenses of this month
            $monthly_exp_q = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
            $monthly_exp = 0;
            if ($monthly_exp_q && $row = mysqli_fetch_assoc($monthly_exp_q)) {
                $monthly_exp = $row['total'] ?? 0;
            }

            if ($monthly_budget > 0) {
                $prev_monthly_exp = $monthly_exp - $amount;
                
                // Exceeded limit boundary
                if ($monthly_exp >= $monthly_budget && $prev_monthly_exp < $monthly_budget) {
                    $alert_msg = "Budget limit exceeded! Spent " . $currency_symbol . number_format($monthly_exp, 2) . " of your limit.";
                    add_notification($conn, $user_id, $alert_msg, 'budget_exceeded');
                    $_SESSION['flash_notification'] = ['message' => $alert_msg, 'type' => 'budget_exceeded'];
                }
                // Approached 80% boundary
                elseif ($monthly_exp >= ($monthly_budget * 0.8) && $prev_monthly_exp < ($monthly_budget * 0.8)) {
                    $alert_msg = "Approaching monthly budget threshold (" . round(($monthly_exp / $monthly_budget) * 100, 0) . "% used).";
                    add_notification($conn, $user_id, $alert_msg, 'budget_warning');
                    $_SESSION['flash_notification'] = ['message' => $alert_msg, 'type' => 'budget_warning'];
                }
            }
        } else {
            // Check savings goal
            $goal_target = $_SESSION['savings_goal_target'] ?? 50000.00;
            $goal_name = $_SESSION['savings_goal_name'] ?? 'New Laptop';

            // Query total balance
            $inc_q = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'income'");
            $total_inc = mysqli_fetch_assoc($inc_q)['total'] ?? 0;
            $exp_q = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'expense'");
            $total_exp = mysqli_fetch_assoc($exp_q)['total'] ?? 0;
            
            $current_balance = $total_inc - $total_exp;
            $prev_balance = $current_balance - $amount;

            if ($goal_target > 0 && $current_balance >= $goal_target && $prev_balance < $goal_target) {
                $goal_msg = "Savings goal achieved! Saved " . $currency_symbol . number_format($current_balance, 2) . " towards '" . htmlspecialchars($goal_name) . "'.";
                add_notification($conn, $user_id, $goal_msg, 'goal_reached');
                $_SESSION['flash_notification'] = ['message' => $goal_msg, 'type' => 'goal_reached'];
            }
        }

        // Redirect back to referring page
        $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        header("Location: " . $referer);
        exit();
    } else {
        echo "Error inserting transaction: " . mysqli_error($conn);
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>
