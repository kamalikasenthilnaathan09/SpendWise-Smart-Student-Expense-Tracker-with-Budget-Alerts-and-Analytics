<?php
// Connect to MySQL server (without specifying DB to ensure we can create it if missing)
$conn = @mysqli_connect("localhost", "root", "", "", 3307);
if (!$conn) {
    $conn = @mysqli_connect("localhost", "root", "", "", 3306);
}

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$db_query = "CREATE DATABASE IF NOT EXISTS spendwise";
if (!mysqli_query($conn, $db_query)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select database
mysqli_select_db($conn, "spendwise");

echo "Database spendwise checked/created successfully.\n";

// Create users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $users_table)) {
    echo "Table 'users' verified successfully.\n";
} else {
    die("Error creating users table: " . mysqli_error($conn));
}

// Create transactions table
$transactions_table = "CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL DEFAULT 'expense',
    category VARCHAR(100) NOT NULL,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $transactions_table)) {
    echo "Table 'transactions' verified successfully.\n";
} else {
    die("Error creating transactions table: " . mysqli_error($conn));
}

// Check if default user exists
$user_check = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
$default_user_id = 0;
if (mysqli_num_rows($user_check) == 0) {
    // Insert default user
    $username = "kamalika";
    $email = "kamalika@spendwise.com";
    $password = password_hash("password123", PASSWORD_DEFAULT);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);
    mysqli_stmt_execute($stmt);
    $default_user_id = mysqli_insert_id($conn);
    echo "Default user 'kamalika' (kamalika@spendwise.com / password123) created.\n";
} else {
    $row = mysqli_fetch_assoc($user_check);
    $default_user_id = $row['id'];
}

// Migrate data from legacy expenses table if it exists
$expenses_exist = mysqli_query($conn, "SHOW TABLES LIKE 'expenses'");
if (mysqli_num_rows($expenses_exist) > 0) {
    echo "Legacy 'expenses' table detected. Migrating data...\n";
    $migrate_query = "INSERT INTO transactions (user_id, title, amount, type, category, transaction_date, created_at)
                      SELECT ?, title, amount, 'expense', category, expense_date, created_at FROM expenses";
    
    $stmt = mysqli_prepare($conn, $migrate_query);
    mysqli_stmt_bind_param($stmt, "i", $default_user_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Data migrated successfully. Dropping legacy 'expenses' table...\n";
        mysqli_query($conn, "DROP TABLE expenses");
    } else {
        echo "Error migrating data: " . mysqli_error($conn) . "\n";
    }
}

// Insert dummy mock data if transactions table is empty
$trans_check = mysqli_query($conn, "SELECT id FROM transactions LIMIT 1");
if (mysqli_num_rows($trans_check) == 0) {
    echo "Transactions table is empty. Populating with realistic demo data...\n";
    $mock_data = [
        ['title' => 'Monthly Salary', 'amount' => 75000.00, 'type' => 'income', 'category' => 'Salary', 'date' => date('Y-m-d', strtotime('-5 days'))],
        ['title' => 'Freelance Writing', 'amount' => 12000.00, 'type' => 'income', 'category' => 'Freelance', 'date' => date('Y-m-d', strtotime('-3 days'))],
        ['title' => 'Grocery Shop', 'amount' => 3450.00, 'type' => 'expense', 'category' => 'Food', 'date' => date('Y-m-d', strtotime('-4 days'))],
        ['title' => 'Cab Fare', 'amount' => 380.00, 'type' => 'expense', 'category' => 'Travel', 'date' => date('Y-m-d', strtotime('-4 days'))],
        ['title' => 'Netflix Subscription', 'amount' => 649.00, 'type' => 'expense', 'category' => 'Entertainment', 'date' => date('Y-m-d', strtotime('-2 days'))],
        ['title' => 'Zomato Lunch Delivery', 'amount' => 750.00, 'type' => 'expense', 'category' => 'Food', 'date' => date('Y-m-d', strtotime('-1 days'))],
        ['title' => 'Electricity Bill', 'amount' => 4500.00, 'type' => 'expense', 'category' => 'Utilities', 'date' => date('Y-m-d', strtotime('-1 days'))],
        ['title' => 'Office Coworking Space', 'amount' => 5000.00, 'type' => 'expense', 'category' => 'Rent', 'date' => date('Y-m-d', strtotime('-1 days'))],
        ['title' => 'Stock Dividends', 'amount' => 3200.00, 'type' => 'income', 'category' => 'Investment', 'date' => date('Y-m-d')]
    ];
    
    $stmt = mysqli_prepare($conn, "INSERT INTO transactions (user_id, title, amount, type, category, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($mock_data as $item) {
        mysqli_stmt_bind_param($stmt, "isdsss", $default_user_id, $item['title'], $item['amount'], $item['type'], $item['category'], $item['date']);
        mysqli_stmt_execute($stmt);
    }
    echo "Mock transaction data populated successfully.\n";
}

echo "Database setup completed successfully.\n";
?>
