<?php
// IF block (access control): not logged in, go to the login page with a message
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please login first to access that page';
    header("Location: " . BASE_URL . "/user/login.php");
    exit();
}

// SELECT query + IF block: read the account again, so a deactivated user is logged out right away
$sql = "SELECT role, status FROM users WHERE user_id = ? LIMIT 1";
$account = mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$_SESSION['user_id']]));
if (!$account || $account['status'] !== 'active') {
    $_SESSION = array();
    $_SESSION['message'] = 'your account has been deactivated';
    header("Location: " . BASE_URL . "/user/login.php");
    exit();
}

// SESSION: use the current role, so a role change by the admin works without logging out
$_SESSION['role'] = $account['role'];
