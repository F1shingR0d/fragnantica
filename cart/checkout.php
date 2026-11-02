<?php
// SESSION + INCLUDES: start the session, connect, require login
session_start();
include('../includes/config.php');
include('../includes/loginCheck.php');

// IF block: stop if the cart is empty
if (!isset($_SESSION["cart_products"]) || count($_SESSION["cart_products"]) === 0) {
    $_SESSION['message'] = 'your cart is empty';
    header('Location: view_cart.php');
    exit();
}

// IF block: checkout only works from the Payment form on the cart page
if (!isset($_POST['checkout'])) {
    header('Location: view_cart.php');
    exit();
}

// VARIABLES: read the payment method and the reference number
$payment_method = $_POST['payment_method'] ?? '';
$reference_no = trim(strip_tags($_POST['reference_no'] ?? ''));

// IF-ELSE IF blocks (validation): cash or online, and online needs a valid reference number
$payError = '';
if ($payment_method !== 'cash' && $payment_method !== 'online') {
    $payError = 'please choose a payment method';
} else if ($payment_method === 'online' && $reference_no === '') {
    $payError = 'please enter the reference number of your online payment';
} else if ($payment_method === 'online' && !preg_match('/^[A-Za-z0-9-]{6,30}$/', $reference_no)) {
    $payError = 'the reference number should be 6 to 30 letters or numbers';
}
// IF block: go back to the cart with the error and the typed values
if ($payError !== '') {
    $_SESSION['message'] = $payError;
    $_SESSION['payMethod'] = $payment_method;
    $_SESSION['payReference'] = $reference_no;
    header('Location: view_cart.php');
    exit();
}
// IF block: cash orders have no reference number
if ($payment_method === 'cash') {
    $reference_no = null;
}

// IF block + SELECT query: the delivery details must be filled in first
$sql = "SELECT customer_id FROM customer WHERE user_id = ? LIMIT 1";
$result = mysqli_execute_query($conn, $sql, [$_SESSION['user_id']]);
if (mysqli_num_rows($result) === 0) {
    $_SESSION['message'] = 'please fill in your delivery details before checking out';
    header('Location: ../user/profile.php');
    exit();
}

// TRY-CATCH block (TRANSACTION): save the whole order, or nothing if a step fails
try {
    mysqli_begin_transaction($conn);

    // PREPARED STATEMENT (INSERT): the order row with its payment method and reference number
    $q = 'INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (?, \'pending\', ?, ?, NOW())';
    $stmt1 = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($stmt1, 'iss', $_SESSION['user_id'], $payment_method, $reference_no);
    mysqli_stmt_execute($stmt1);
    $order_id = mysqli_insert_id($conn);

    // PREPARED STATEMENT (INSERT): one row per product in the order
    $q2 = 'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)';
    $stmt2 = mysqli_prepare($conn, $q2);
    mysqli_stmt_bind_param($stmt2, 'iiid', $order_id, $product_code, $product_qty, $product_price);

    // PREPARED STATEMENT (UPDATE): lower the stock
    $q3 = 'UPDATE stock SET quantity = quantity - ? WHERE product_id = ?';
    $stmt3 = mysqli_prepare($conn, $q3);
    mysqli_stmt_bind_param($stmt3, 'ii', $product_qty, $product_code);

    // SELECT ... FOR UPDATE: lock the stock row while checking it
    $q4 = 'SELECT p.name, p.sell_price, s.quantity FROM product p INNER JOIN stock s USING (product_id) WHERE p.product_id = ? FOR UPDATE';

    // FOREACH loop: check the stock, use today's price, save the item, lower the stock
    foreach ($_SESSION["cart_products"] as $cart_itm) {
        $product_qty = $cart_itm["product_qty"];
        $product_code = $cart_itm["product_id"];

        $row = mysqli_fetch_assoc(mysqli_execute_query($conn, $q4, [$product_code]));
        if (!$row || $row['quantity'] < $product_qty) {
            mysqli_rollback($conn);
            $_SESSION['message'] = "not enough stock for {$cart_itm['product_name']}, please change the quantity";
            header('Location: view_cart.php');
            exit();
        }
        $product_price = $row['sell_price'];

        mysqli_stmt_execute($stmt2);
        mysqli_stmt_execute($stmt3);
    }

    // COMMIT: save everything, empty the cart, go to My Orders
    mysqli_commit($conn);
    unset($_SESSION['cart_products']);
    $_SESSION['success'] = "order #{$order_id} placed, thank you!";
    header('Location: ../user/myorders.php');
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'checkout failed, please try again';
    header('Location: view_cart.php');
}
