<?php
// SESSION + INCLUDES: start the session, connect, admin only
session_start();
include('../includes/config.php');
include('../includes/adminCheck.php');

// IF block: stop if the id is not a number
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    header('Location: index.php');
    exit;
}

// TRY-CATCH block (TRANSACTION): delete the stock row, then the product; blocked if it is in an order
mysqli_begin_transaction($conn);

try {
    $stockStmt = mysqli_prepare($conn, 'DELETE FROM stock WHERE product_id = ?');
    mysqli_stmt_bind_param($stockStmt, 'i', $product_id);
    mysqli_stmt_execute($stockStmt);

    $productStmt = mysqli_prepare($conn, 'DELETE FROM product WHERE product_id = ?');
    mysqli_stmt_bind_param($productStmt, 'i', $product_id);
    mysqli_stmt_execute($productStmt);

    mysqli_commit($conn);
    $_SESSION['success'] = 'product deleted';
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'This product cannot be deleted because it is already in a customer order';
}

header('Location: index.php');
exit;
