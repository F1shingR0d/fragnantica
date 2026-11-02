<?php
// SESSION + INCLUDES: start the session, connect, load functions, require login, show the navbar
session_start();
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/loginCheck.php');
include('../includes/header.php');

// SELECT query (view): this customer's orders with their payment and grand total
$sql = "SELECT order_id, status, payment_method, reference_no, date_placed, total FROM order_totals_view WHERE user_id = ? ORDER BY order_id DESC";
$orders = mysqli_execute_query($conn, $sql, [$_SESSION['user_id']]);
?>
<div class="container mt-4">
    <?php include('../includes/alert.php'); ?>
    <h2>My Orders</h2>
    <?php
    // IF block: message when there are no orders
    if (mysqli_num_rows($orders) === 0) {
        echo "<p>You have no orders yet. <a href='../index.php'>Shop now</a></p>";
    }
    // WHILE loop: one card per order
    while ($order = mysqli_fetch_assoc($orders)) {
        echo "<div class='card mb-3'>";
        echo "<div class='card-header'>Order #{$order['order_id']} &middot; {$order['date_placed']} &middot; <strong>{$order['status']}</strong></div>";
        echo "<div class='card-body'><div class='table-responsive'><table class='table table-sm'>";
        echo "<thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th><th></th></tr></thead><tbody>";

        // SELECT query + WHILE loop: the products in this order
        $sql = "SELECT product_id, product_name, quantity, price, subtotal FROM order_details_view WHERE order_id = ?";
        $items = mysqli_execute_query($conn, $sql, [$order['order_id']]);
        while ($row = mysqli_fetch_assoc($items)) {
            // IF block: "Write a review" link only for completed orders
            $reviewLink = '';
            if ($order['status'] === 'completed') {
                $reviewLink = "<a href='../product/view.php?id={$row['product_id']}'>Write a review</a>";
            }
            echo "<tr>";
            echo "<td>{$row['product_name']}</td>";
            echo "<td>{$row['quantity']}</td>";
            echo "<td>{$row['price']}</td>";
            echo "<td>{$row['subtotal']}</td>";
            echo "<td>{$reviewLink}</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        // IF-ELSE block: the payment method, with the reference number for online payments
        if ($order['payment_method'] === 'online') {
            $payment = "Online payment &middot; reference no. {$order['reference_no']}";
        } else {
            $payment = "Cash on delivery";
        }
        echo "<p class='mb-1'>Payment: {$payment}</p>";
        echo "<p class='text-end mb-0'><strong>Grand Total: " . peso($order['total']) . "</strong></p>";

        // IF block (HTML DETAILS + FORM): Cancel order only while pending; <details> asks "are you sure?" without JavaScript
        if ($order['status'] === 'pending') {
            echo "<details class='confirm text-end mt-2'>
                <summary class='btn btn-sm btn-outline-danger'>Cancel order</summary>
                <form method='POST' action='cancelOrder.php' class='confirm-box'>
                    <span>Cancel order #{$order['order_id']}?</span>
                    <input type='hidden' name='order_id' value='{$order['order_id']}' />
                    <button type='submit' class='btn btn-sm btn-danger'>Yes, cancel it</button>
                </form>
            </details>";
        }
        echo "</div></div>";
    }
    ?>
</div>
<?php
include('../includes/footer.php');
?>
