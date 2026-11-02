<?php
// SESSION + INCLUDES: start the session, connect, require login
session_start();
include('../includes/config.php');
include('../includes/loginCheck.php');

// IF block: only the Cancel order button on My Orders can open this script
if (!isset($_POST['order_id'])) {
    header('Location: myorders.php');
    exit();
}
$order_id = (int)$_POST['order_id'];

// TRY-CATCH block (TRANSACTION): cancel the order and put the items back into stock
try {
    mysqli_begin_transaction($conn);

    // SELECT ... FOR UPDATE + IF block: the order must be this customer's and still pending
    $sql = "SELECT status FROM orders WHERE order_id = ? AND user_id = ? FOR UPDATE";
    $order = mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$order_id, $_SESSION['user_id']]));
    if (!$order || $order['status'] !== 'pending') {
        mysqli_rollback($conn);
        $_SESSION['message'] = 'only your pending orders can be cancelled';
        header('Location: myorders.php');
        exit();
    }

    // PREPARED STATEMENT (UPDATE): mark the order as cancelled
    $stmt1 = mysqli_prepare($conn, "UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
    mysqli_stmt_bind_param($stmt1, 'i', $order_id);
    mysqli_stmt_execute($stmt1);

    // PREPARED STATEMENT (UPDATE): return the quantities to stock
    $stmt2 = mysqli_prepare($conn, 'UPDATE stock s INNER JOIN order_items oi ON oi.product_id = s.product_id SET s.quantity = s.quantity + oi.quantity WHERE oi.order_id = ?');
    mysqli_stmt_bind_param($stmt2, 'i', $order_id);
    mysqli_stmt_execute($stmt2);

    mysqli_commit($conn);
    $_SESSION['success'] = "order #{$order_id} was cancelled";
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'could not cancel the order, please try again';
}
header('Location: myorders.php');
