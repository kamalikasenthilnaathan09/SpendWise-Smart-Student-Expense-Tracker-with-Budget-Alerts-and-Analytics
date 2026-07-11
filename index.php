<?php
session_start();

// Traffic controller: Redirect based on auth status
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>