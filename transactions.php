<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION['user_id'];

// Filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$sort_order = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'ASC' : 'DESC';

// Build SQL Query
$sql = "SELECT * FROM transactions WHERE user_id = $user_id";

if ($search !== '') {
    $sql .= " AND (title LIKE '%$search%' OR category LIKE '%$search%')";
}
if ($type_filter === 'income' || $type_filter === 'expense') {
    $sql .= " AND type = '$type_filter'";
}
if ($category_filter !== '') {
    $sql .= " AND category = '$category_filter'";
}

$sql .= " ORDER BY transaction_date $sort_order, id DESC";

$transactions_res = mysqli_query($conn, $sql);

// Fetch categories for filter dropdown
$cat_query = mysqli_query($conn, "SELECT DISTINCT category FROM transactions WHERE user_id = $user_id");
$categories = [];
while ($cat_row = mysqli_fetch_assoc($cat_query)) {
    $categories[] = $cat_row['category'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Transactions History</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,.04);
            margin-bottom: 25px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group.btn-group {
            flex: 0;
            min-width: auto;
            display: flex;
            gap: 10px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #f9fafb;
            transition: 0.2s;
        }

        .filter-control:focus {
            border-color: #2563eb;
            background: white;
        }

        .btn-filter {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-size: 14px;
            border: none;
        }

        .btn-apply {
            background: #2563eb;
            color: white;
        }

        .btn-apply:hover {
            background: #1d4ed8;
        }

        .btn-clear {
            background: #f3f4f6;
            color: #4b5563;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            line-height: 20px;
        }

        .btn-clear:hover {
            background: #e5e7eb;
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

            <!-- Title Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="font-size: 26px; font-weight: 700; color: #111827;">Transactions Registry</h2>
                <button class="view-btn" id="openModalBtn" style="background: linear-gradient(to right, #2563eb, #1d4ed8);">
                    <i class="fa-solid fa-plus"></i> Add Transaction
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="filter-section">
                <form class="filter-form" method="GET" action="transactions.php">
                    <div class="filter-group">
                        <label for="search">Keywords</label>
                        <input type="text" name="search" id="search" class="filter-control" placeholder="Search by title..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="filter-control">
                            <option value="">All Types</option>
                            <option value="expense" <?php echo ($type_filter === 'expense') ? 'selected' : ''; ?>>Expense</option>
                            <option value="income" <?php echo ($type_filter === 'income') ? 'selected' : ''; ?>>Income</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" class="filter-control">
                            <option value="">All Categories</option>
                            <option value="Food" <?php echo ($category_filter === 'Food') ? 'selected' : ''; ?>>Food & Dining</option>
                            <option value="Travel" <?php echo ($category_filter === 'Travel') ? 'selected' : ''; ?>>Travel & Fuel</option>
                            <option value="Shopping" <?php echo ($category_filter === 'Shopping') ? 'selected' : ''; ?>>Shopping</option>
                            <option value="Entertainment" <?php echo ($category_filter === 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                            <option value="Utilities" <?php echo ($category_filter === 'Utilities') ? 'selected' : ''; ?>>Utilities & Bills</option>
                            <option value="Salary" <?php echo ($category_filter === 'Salary') ? 'selected' : ''; ?>>Salary & Income</option>
                            <option value="Investment" <?php echo ($category_filter === 'Investment') ? 'selected' : ''; ?>>Investments</option>
                            <option value="Others" <?php echo ($category_filter === 'Others') ? 'selected' : ''; ?>>Others</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="sort">Date Sort</label>
                        <select name="sort" id="sort" class="filter-control">
                            <option value="desc" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="asc" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>

                    <div class="filter-group btn-group">
                        <button type="submit" class="btn-filter btn-apply">Apply</button>
                        <a href="transactions.php" class="btn-filter btn-clear">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="transactions" style="margin-top: 0;">
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
                        if (mysqli_num_rows($transactions_res) > 0) {
                            while ($row = mysqli_fetch_assoc($transactions_res)) {
                                $date = date("d M Y", strtotime($row['transaction_date']));
                                $type_class = ($row['type'] == 'income') ? 'badge-income' : 'badge-expense';
                                $amount_class = ($row['type'] == 'income') ? 'text-success' : 'text-danger';
                                $amount_prefix = ($row['type'] == 'income') ? '+' : '-';
                                
                                echo "<tr>
                                    <td>{$date}</td>
                                    <td><span style='background: #f3f4f6; padding: 6px 12px; border-radius: 8px; font-weight: 500; color: #111827;'>{$row['category']}</span></td>
                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                    <td><span class='badge {$type_class}'>{$row['type']}</span></td>
                                    <td class='{$amount_class}' style='font-weight: 600;'>{$amount_prefix}" . (isset($_SESSION['currency']) ? ($_SESSION['currency'] === 'USD' ? '$' : ($_SESSION['currency'] === 'EUR' ? '€' : ($_SESSION['currency'] === 'GBP' ? '£' : '₹'))) : '₹') . number_format($row['amount'], 2) . "</td>
                                    <td>
                                        <a href='delete.php?id={$row['id']}' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this transaction?\")' title='Delete'>
                                            <i class='fa-solid fa-trash-can'></i>
                                        </a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align: center; color: #6b7280; padding: 40px;'>No transactions matching filters were found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div> <!-- /content -->

    </div> <!-- /main-content -->

</div> <!-- /wrapper -->

<script>
    // Local instant filtering for search input in header
    const headerSearchBar = document.getElementById("searchBar");
    if (headerSearchBar) {
        headerSearchBar.onkeyup = function() {
            const query = headerSearchBar.value.toLowerCase();
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
    }
</script>

</body>
</html>

</body>
</html>
