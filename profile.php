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

// Handle Profile Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if (empty($username) || empty($email)) {
        $error_msg = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format!";
    } else {
        // Check if email already exists for another user
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($check, "si", $email, $user_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error_msg = "Email is already in use by another account!";
        } else {
            $update = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, "ssi", $username, $email, $user_id);

            if (mysqli_stmt_execute($update)) {
                $_SESSION['username'] = $username;
                $success_msg = "Profile updated successfully!";
            } else {
                $error_msg = "Database error: Failed to update profile.";
            }
        }
    }
}

// Fetch user detail
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_res = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_res);

$joined_date = isset($user_data['joined_date']) ? date("d M Y", strtotime($user_data['joined_date'])) : 'N/A';
$theme = $user_data['theme'] ?? 'light';

// Calculate Totals for Profile
// Income
$inc_q = mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND type = 'income'");
$inc_row = mysqli_fetch_assoc($inc_q);
$totalIncome = $inc_row['total'] ?? 0;

// Expense & Transaction Count
$exp_q = mysqli_query($conn, "SELECT SUM(amount) AS total, COUNT(*) AS count FROM transactions WHERE user_id = $user_id AND type = 'expense'");
$exp_row = mysqli_fetch_assoc($exp_q);
$totalExpense = $exp_row['total'] ?? 0;

$total_trans_q = mysqli_query($conn, "SELECT COUNT(*) AS count FROM transactions WHERE user_id = $user_id");
$total_trans_row = mysqli_fetch_assoc($total_trans_q);
$totalTransactionsCount = $total_trans_row['count'] ?? 0;

$totalSavings = $totalIncome - $totalExpense;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - User Profile</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid #f3f4f6;
            text-align: center;
        }

        body.dark-theme .profile-card {
            background: #1e293b;
            border-color: #334155;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #9333ea);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 42px;
            margin: 0 auto 20px;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.2);
        }

        body.dark-theme .profile-avatar {
            border-color: #0f172a;
        }

        .profile-card h3 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 5px;
        }

        body.dark-theme .profile-card h3 {
            color: #f8fafc;
        }

        .profile-card p.email {
            color: #6b7280;
            margin-bottom: 20px;
            font-size: 14px;
        }

        body.dark-theme .profile-card p.email {
            color: #94a3b8;
        }

        .profile-details-list {
            text-align: left;
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
            margin-top: 20px;
        }

        body.dark-theme .profile-details-list {
            border-top-color: #334155;
        }

        .profile-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .profile-detail-item span.label {
            color: #6b7280;
            font-weight: 500;
        }

        .profile-detail-item span.val {
            color: #111827;
            font-weight: 600;
        }

        body.dark-theme .profile-detail-item span.label {
            color: #94a3b8;
        }

        body.dark-theme .profile-detail-item span.val {
            color: #e2e8f0;
        }

        .financial-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 600px) {
            .financial-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        .financial-stat-card {
            background: white;
            padding: 22px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        body.dark-theme .financial-stat-card {
            background: #1e293b;
            border-color: #334155;
        }

        .financial-stat-info h4 {
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        body.dark-theme .financial-stat-info h4 {
            color: #94a3b8;
        }

        .financial-stat-info p {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }

        body.dark-theme .financial-stat-info p {
            color: #f8fafc;
        }

        .financial-stat-card .icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
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
                    <i class="fa-solid fa-user-tie text-success" style="font-size: 24px;"></i> My Profile
                </h2>
                <p style="color: #6b7280; margin-top: 5px;">View your account details and overall financial overview statistics.</p>
            </div>

            <!-- Profile Alerts -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="profile-container">

                <!-- Left Profile Summary Card -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php 
                        $first_letter = strtoupper(substr($user_data['username'] ?? 'U', 0, 1));
                        echo htmlspecialchars($first_letter);
                        ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user_data['username'] ?? 'User'); ?></h3>
                    <p class="email"><?php echo htmlspecialchars($user_data['email'] ?? 'No email associated'); ?></p>
                    
                    <button class="view-btn" id="openEditProfileBtn" style="background: linear-gradient(to right, #2563eb, #1d4ed8); width: 100%; border: none;">
                        <i class="fa-solid fa-user-pen"></i> Edit Profile
                    </button>

                    <div class="profile-details-list">
                        <div class="profile-detail-item">
                            <span class="label">Joined Date</span>
                            <span class="val"><?php echo $joined_date; ?></span>
                        </div>
                        <div class="profile-detail-item">
                            <span class="label">Total Logs</span>
                            <span class="val"><?php echo $totalTransactionsCount; ?> entries</span>
                        </div>
                    </div>
                </div>

                <!-- Right Financial Summary Metrics -->
                <div class="financial-summary-grid">
                    
                    <!-- Total Income -->
                    <div class="financial-stat-card">
                        <div class="financial-stat-info">
                            <h4>Total Income</h4>
                            <p>₹<?php echo number_format($totalIncome, 2); ?></p>
                        </div>
                        <div class="icon-wrap" style="background: #dcfce7; color: #16a34a;">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>

                    <!-- Total Expenses -->
                    <div class="financial-stat-card">
                        <div class="financial-stat-info">
                            <h4>Total Expense</h4>
                            <p>₹<?php echo number_format($totalExpense, 2); ?></p>
                        </div>
                        <div class="icon-wrap" style="background: #fee2e2; color: #ef4444;">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>

                    <!-- Cumulative Savings -->
                    <div class="financial-stat-card" style="grid-column: span 2;">
                        <div class="financial-stat-info">
                            <h4>Accumulated Net Savings</h4>
                            <p style="font-size: 26px; color: #2563eb;">₹<?php echo number_format($totalSavings, 2); ?></p>
                        </div>
                        <div class="icon-wrap" style="background: #eff6ff; color: #2563eb; width: 56px; height: 56px; font-size: 24px;">
                            <i class="fa-solid fa-piggy-bank"></i>
                        </div>
                    </div>

                </div>

            </div>

        </div> <!-- /content -->

    </div> <!-- /main-content -->

</div> <!-- /wrapper -->

<!-- Edit Profile Modal -->
<div class="modal" id="editProfileModal">
    <div class="modal-content">
        <span class="close-btn" id="closeProfileModalBtn">&times;</span>
        <h2 class="modal-title"><i class="fa-solid fa-user-pen text-success"></i> Edit Profile</h2>
        
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
            </div>
            
            <button type="submit" class="btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<script>
    // Modal Interaction
    const editModal = document.getElementById("editProfileModal");
    const openEditBtn = document.getElementById("openEditProfileBtn");
    const closeEditBtn = document.getElementById("closeProfileModalBtn");

    openEditBtn.onclick = function() {
        editModal.classList.add("show");
    }

    closeEditBtn.onclick = function() {
        editModal.classList.remove("show");
    }

    window.onclick = function(event) {
        if (event.target === editModal) {
            editModal.classList.remove("show");
        }
    }

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
