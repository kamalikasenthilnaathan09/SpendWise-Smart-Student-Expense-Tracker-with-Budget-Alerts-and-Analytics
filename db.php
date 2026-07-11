<?php
$conn = @mysqli_connect("localhost", "root", "", "spendwise", 3307);
if (!$conn) {
    $conn = @mysqli_connect("localhost", "root", "", "spendwise", 3306);
}

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>