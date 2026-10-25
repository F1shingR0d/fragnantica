<?php
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');

// activate or deactivate a coupon: admins only
require_admin();

// only accept the form (POST), never a link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$coupon_id = filter_input(INPUT_POST, 'coupon_id', FILTER_VALIDATE_INT);
$status = post_value('status');

if (!csrf_valid()) {
    $_SESSION['message'] = 'Your session expired. Please try again.';
} else if (!$coupon_id || !in_array($status, array('active', 'inactive'), true)) {
    $_SESSION['message'] = 'Invalid request.';
} else {
    $coupon = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT code FROM coupon WHERE coupon_id = ? LIMIT 1", [$coupon_id]));
    if (!$coupon) {
        $_SESSION['message'] = 'Coupon not found.';
    } else {
        mysqli_execute_query($conn, "UPDATE coupon SET status = ? WHERE coupon_id = ?", [$status, $coupon_id]);
        $_SESSION['success'] = $status === 'inactive'
            ? 'Coupon ' . e($coupon['code']) . ' was deactivated. Customers can no longer use it.'
            : 'Coupon ' . e($coupon['code']) . ' was activated.';
    }
}

header("Location: index.php");
exit();
