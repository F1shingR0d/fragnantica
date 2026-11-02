<?php
// SESSION + INCLUDES: start the session, connect, admin only
session_start();
include('../../includes/config.php');
include('../../includes/adminCheck.php');

// VARIABLES: the user id and the new status from the link
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = $_GET['status'] ?? '';

// IF-ELSE IF-ELSE block: check the request, then activate or deactivate (a deactivated user cannot log in)
if (!$user_id || ($status !== 'active' && $status !== 'inactive')) {
    $_SESSION['message'] = 'invalid request';
} else if ($user_id == $_SESSION['user_id']) {
    $_SESSION['message'] = 'you cannot deactivate your own account';
} else {
    $sql = "UPDATE users SET status = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $status, $user_id);
    mysqli_stmt_execute($stmt);
    if ($status === 'inactive') {
        $_SESSION['success'] = 'user deactivated';
    } else {
        $_SESSION['success'] = 'user activated';
    }
}
header("Location: index.php");
