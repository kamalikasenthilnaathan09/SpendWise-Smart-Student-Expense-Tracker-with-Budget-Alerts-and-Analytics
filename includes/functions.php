<?php
// Core Helper Functions for SpendWise Premium

/**
 * Add a user-specific notification in the database
 */
if (!function_exists('add_notification')) {
    function add_notification($conn, $user_id, $message, $type) {
        $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $message, $type);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Return a human-readable interval format
 */
if (!function_exists('get_time_ago')) {
    function get_time_ago($timestamp_str) {
        $time = strtotime($timestamp_str);
        $difference = time() - $time;
        
        if ($difference < 60) {
            return "Just now";
        } elseif ($difference < 3600) {
            $mins = round($difference / 60);
            return $mins . " min" . ($mins > 1 ? "s" : "") . " ago";
        } elseif ($difference < 86400) {
            $hours = round($difference / 3600);
            return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } else {
            $days = round($difference / 86400);
            return $days . " day" . ($days > 1 ? "s" : "") . " ago";
        }
    }
}
?>
