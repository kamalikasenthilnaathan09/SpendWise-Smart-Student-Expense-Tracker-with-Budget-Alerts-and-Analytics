<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency = $_POST['currency'];
    $monthly_budget = floatval($_POST['monthly_budget']);
    $savings_goal_name = trim($_POST['savings_goal_name']);
    $savings_goal_target = floatval($_POST['savings_goal_target']);
    $savings_goal_current = floatval($_POST['savings_goal_current']);
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $theme = $_POST['theme'];

    if ($monthly_budget <= 0 || empty($savings_goal_name) || $savings_goal_target <= 0) {
        $error_msg = "Please fill in all required fields with positive values.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET currency = ?, monthly_budget = ?, savings_goal_name = ?, savings_goal_target = ?, savings_goal_current = ?, notifications_enabled = ?, theme = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sdssdiii", $currency, $monthly_budget, $savings_goal_name, $savings_goal_target, $savings_goal_current, $notifications_enabled, $theme, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update session variables immediately
            $_SESSION['currency'] = $currency;
            $_SESSION['monthly_budget'] = $monthly_budget;
            $_SESSION['savings_goal_name'] = $savings_goal_name;
            $_SESSION['savings_goal_target'] = $savings_goal_target;
            $_SESSION['savings_goal_current'] = $savings_goal_current;
            $_SESSION['notifications_enabled'] = $notifications_enabled;
            $_SESSION['theme'] = $theme;
            
            $success_msg = "Settings updated successfully!";
        } else {
            $error_msg = "Failed to update settings: " . mysqli_error($conn);
        }
    }
}

// Fetch latest settings from DB to be safe
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user_settings = mysqli_fetch_assoc($res);

// Populate variables
$theme = $user_settings['theme'] ?? 'light';
$currency = $user_settings['currency'] ?? 'INR';
$monthly_budget = $user_settings['monthly_budget'] ?? 50000.00;
$savings_goal_name = $user_settings['savings_goal_name'] ?? 'New Laptop';
$savings_goal_target = $user_settings['savings_goal_target'] ?? 50000.00;
$savings_goal_current = $user_settings['savings_goal_current'] ?? 33433.00;
$notifications_enabled = $user_settings['notifications_enabled'] ?? 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise Settings</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .settings-card {
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid #f3f4f6;
            margin-bottom: 25px;
        }

        body.dark-theme .settings-card {
            background: #1e293b;
            border-color: #334155;
        }

        .settings-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #111827;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 15px;
        }

        body.dark-theme .settings-title {
            color: #f8fafc;
            border-bottom-color: #334155;
        }

        .alert-success {
            padding: 15px;
            background-color: #dcfce7;
            color: #15803d;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-danger {
            padding: 15px;
            background-color: #fee2e2;
            color: #b91c1c;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media(max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .toggle-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #2563eb;
        }

        input:checked + .slider:before {
            transform: translateX(24px);
        }
    </style>
</head>

<body class="<?php echo ($theme === 'dark') ? 'dark-theme' : ''; ?>">

<div class="wrapper">

    <!-- Sidebar Include -->
    <?php include("includes/sidebar.php"); ?>

    <div class="main-content">

        <!-- Header Include -->
        <?php include("includes/header.php"); ?>

        <div class="content">

            <!-- Title Header -->
            <div style="margin-bottom: 25px;">
                <h2 style="font-size: 26px; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-sliders text-success" style="font-size: 24px;"></i> System Settings
                </h2>
                <p style="color: #6b7280; margin-top: 5px;">Manage your currencies, budgets, targets, and system preferences.</p>
            </div>

            <!-- Settings Update Alert -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- Settings Form -->
            <div class="settings-card">
                <form method="POST">
                    <h3 class="settings-title">Preferences</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="currency">Primary Currency</label>
                            <select name="currency" id="currency" class="form-control" required>
                                <option value="INR" <?php echo ($currency === 'INR') ? 'selected' : ''; ?>>INR (₹) - Indian Rupee</option>
                                <option value="USD" <?php echo ($currency === 'USD') ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                                <option value="EUR" <?php echo ($currency === 'EUR') ? 'selected' : ''; ?>>EUR (€) - Euro</option>
                                <option value="GBP" <?php echo ($currency === 'GBP') ? 'selected' : ''; ?>>GBP (£) - British Pound</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="theme">Display Theme</label>
                            <select name="theme" id="theme" class="form-control" required>
                                <option value="light" <?php echo ($theme === 'light') ? 'selected' : ''; ?>>Light Mode</option>
                                <option value="dark" <?php echo ($theme === 'dark') ? 'selected' : ''; ?>>Dark Mode</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="settings-title" style="margin-top: 30px;">Budgeting & Goals</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="monthly_budget">Monthly Spend Limit</label>
                            <input type="number" step="0.01" name="monthly_budget" id="monthly_budget" class="form-control" value="<?php echo htmlspecialchars($monthly_budget); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="savings_goal_name">Savings Goal Name</label>
                            <input type="text" name="savings_goal_name" id="savings_goal_name" class="form-control" value="<?php echo htmlspecialchars($savings_goal_name); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="savings_goal_target">Savings Goal Target</label>
                            <input type="number" step="0.01" name="savings_goal_target" id="savings_goal_target" class="form-control" value="<?php echo htmlspecialchars($savings_goal_target); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="savings_goal_current">Current Savings Allocated</label>
                            <input type="number" step="0.01" name="savings_goal_current" id="savings_goal_current" class="form-control" value="<?php echo htmlspecialchars($savings_goal_current); ?>" required>
                        </div>
                    </div>

                    <h3 class="settings-title" style="margin-top: 30px;">Notifications</h3>
                    <div class="form-group">
                        <div class="toggle-group">
                            <label class="switch">
                                <input type="checkbox" name="notifications_enabled" <?php echo ($notifications_enabled == 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span style="font-weight: 600; color: #4b5563; font-size: 14px;">Enable in-app reminders & spend threshold alerts</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 30px; width: auto; padding-left: 40px; padding-right: 40px;">Save Settings</button>
                </form>
            </div>

        </div> <!-- /content -->

    </div> <!-- /main-content -->

</div> <!-- /wrapper -->

<script>
    // Header search bar hook
    const headerSearchBar = document.getElementById("searchBar");
    headerSearchBar.onkeypress = function(e) {
        if (e.key === 'Enter') {
            window.location.href = 'transactions.php?search=' + encodeURIComponent(headerSearchBar.value);
        }
    }
</script>

</body>
</html>
