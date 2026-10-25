<?php
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');

// Coupon CRUD (delete): admins only
require_admin();

// only accept the form (POST) with its CSRF token, never a plain link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$coupon_id = filter_input(INPUT_POST, 'coupon_id', FILTER_VALIDATE_INT);

if (!csrf_valid()) {
    $_SESSION['message'] = 'Your session expired. Please try again.';
} else if (!$coupon_id) {
    $_SESSION['message'] = 'Invalid request.';
} else {
    $coupon = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT code FROM coupon WHERE coupon_id = ? LIMIT 1", [$coupon_id]));
    if (!$coupon) {
        $_SESSION['message'] = 'Coupon not found.';
    } else {
        try {
            mysqli_execute_query($conn, "DELETE FROM coupon WHERE coupon_id = ?", [$coupon_id]);
            $_SESSION['success'] = 'Coupon ' . e($coupon['code']) . ' was deleted.';
        } catch (mysqli_sql_exception $e) {
            // the foreign key on orders.coupon_id stops this when an order used the coupon
            $_SESSION['message'] = 'Coupon ' . e($coupon['code']) . ' was used on an order, so it cannot be deleted. Deactivate it instead.';
        }
    }
}

header("Location: index.php");
exit();
