<?php
session_start();
include('../includes/config.php');

// only admins can delete perfumes
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'admin access only, please log in with an admin account';
    header("Location: ../user/login.php");
    exit();
}

$perfume_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$perfume_id) {
    header("Location: index.php");
    exit();
}

// get the photo path so the file can be removed too
$sql = "SELECT img_path FROM perfume WHERE perfume_id = ? LIMIT 1";
$result = mysqli_execute_query($conn, $sql, [$perfume_id]);
$perfume = mysqli_fetch_assoc($result);

if (!$perfume) {
    $_SESSION['message'] = 'perfume not found';
    header("Location: index.php");
    exit();
}

// delete the stock first because it points to the perfume
mysqli_begin_transaction($conn);

try {
    $stockStmt = mysqli_prepare($conn, 'DELETE FROM stock WHERE perfume_id = ?');
    mysqli_stmt_bind_param($stockStmt, 'i', $perfume_id);
    mysqli_stmt_execute($stockStmt);

    $perfumeStmt = mysqli_prepare($conn, 'DELETE FROM perfume WHERE perfume_id = ?');
    mysqli_stmt_bind_param($perfumeStmt, 'i', $perfume_id);
    mysqli_stmt_execute($perfumeStmt);

    mysqli_commit($conn);
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'Could not delete the perfume: ' . $e->getMessage();
    header("Location: index.php");
    exit();
}

if (file_exists($perfume['img_path'])) {
    unlink($perfume['img_path']);
}

$_SESSION['success'] = 'Perfume deleted';
header("Location: index.php");
exit();
