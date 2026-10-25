<?php
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');

// Coupon CRUD (update): admins only
require_admin();

if (!isset($_POST['submit'])) {
    header("Location: index.php");
    exit();
}

$coupon_id = filter_input(INPUT_POST, 'coupon_id', FILTER_VALIDATE_INT);
$exists = $coupon_id && mysqli_num_rows(mysqli_execute_query($conn, "SELECT coupon_id FROM coupon WHERE coupon_id = ? LIMIT 1", [$coupon_id])) === 1;
if (!$exists) {
    $_SESSION['message'] = 'Coupon not found.';
    header("Location: index.php");
    exit();
}
$back = "edit.php?id={$coupon_id}";

// validation (all in PHP)
list($data, $errors, $old) = validate_coupon_input($conn, $coupon_id);

if (!csrf_valid()) {
    $_SESSION['message'] = 'Your session expired. Please try again.';
    keep_form('coupon_edit_' . $coupon_id, array(), $old);
    header("Location: {$back}");
    exit();
}
if ($errors) {
    keep_form('coupon_edit_' . $coupon_id, $errors, $old);
    header("Location: {$back}");
    exit();
}

try {
    $sql = "UPDATE coupon SET code=?, description=?, discount_type=?, discount_value=?, min_order=?, max_uses=?, starts_at=?, expires_at=?, status=?
            WHERE coupon_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssddisssi', $data['code'], $data['description'], $data['discount_type'], $data['discount_value'],
        $data['min_order'], $data['max_uses'], $data['starts_at'], $data['expires_at'], $data['status'], $coupon_id);
    mysqli_stmt_execute($stmt);
} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = 'Could not update the coupon. Please try again.';
    keep_form('coupon_edit_' . $coupon_id, array(), $old);
    header("Location: {$back}");
    exit();
}

$_SESSION['success'] = 'Coupon ' . e($data['code']) . ' was updated.';
header("Location: index.php");
exit();
