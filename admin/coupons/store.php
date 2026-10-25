<?php
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');

// Coupon CRUD (create): admins only
require_admin();

if (!isset($_POST['submit'])) {
    header("Location: create.php");
    exit();
}

// validation (all in PHP)
list($data, $errors, $old) = validate_coupon_input($conn);

if (!csrf_valid()) {
    $_SESSION['message'] = 'Your session expired. Please try again.';
    keep_form('coupon_create', array(), $old);
    header("Location: create.php");
    exit();
}
if ($errors) {
    keep_form('coupon_create', $errors, $old);
    header("Location: create.php");
    exit();
}

try {
    $sql = "INSERT INTO coupon (code, description, discount_type, discount_value, min_order, max_uses, starts_at, expires_at, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssddisss', $data['code'], $data['description'], $data['discount_type'], $data['discount_value'],
        $data['min_order'], $data['max_uses'], $data['starts_at'], $data['expires_at'], $data['status']);
    mysqli_stmt_execute($stmt);
} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = 'Could not create the coupon. Please try again.';
    keep_form('coupon_create', array(), $old);
    header("Location: create.php");
    exit();
}

$_SESSION['success'] = 'Coupon ' . e($data['code']) . ' was created.';
header("Location: index.php");
exit();
