<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Read json data
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['theme'])) {
    $theme = ($data['theme'] === 'dark') ? 'dark' : 'light';
    
    $stmt = mysqli_prepare($conn, "UPDATE users SET theme = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $theme, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['theme'] = $theme;
        echo json_encode(["status" => "success", "theme" => $theme]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Database update failed"]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["error" => "Invalid request"]);
?>
