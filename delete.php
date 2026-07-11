<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $transaction_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Secure owner-based delete
    $stmt = mysqli_prepare($conn, "DELETE FROM transactions WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $transaction_id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        // Redirect back to referring page or dashboard
        $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        header("Location: " . $referer);
        exit();
    } else {
        echo "Error deleting transaction: " . mysqli_stmt_error($stmt);
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>