<?php
// SESSION + INCLUDES: start the session, connect, load functions, require login, show the navbar
session_start();
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/loginCheck.php');
include('../includes/header.php');
?>
<div class="container mt-4">
    <?php include('../includes/alert.php'); ?>
    <h1 class="page-title">Your Cart</h1>
    <?php
    // IF-ELSE block: empty cart message, or the cart table
    if (!isset($_SESSION["cart_products"]) || count($_SESSION["cart_products"]) === 0) {
        echo "<p class='empty-note'>Your cart is empty. <a href='../index.php'>Shop now</a></p>";
    } else {
    ?>
        <!-- HTML FORM + TABLE: cart items with quantity boxes and remove checkboxes -->
        <form method="POST" action="cart_update.php">
            <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Quantity</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // FOREACH loop: one row per cart item, adds up the total
                    $total = 0;
                    foreach ($_SESSION["cart_products"] as $cart_itm) {
                        $product_name = $cart_itm["product_name"];
                        $product_qty = $cart_itm["product_qty"];
                        $product_price = $cart_itm["product_price"];
                        $product_code = $cart_itm["product_id"];
                        $subtotal = ($product_price * $product_qty);

                        echo '<tr>';
                        echo '<td><input type="text" class="form-control qty-input" name="product_qty[' . $product_code . ']" value="' . $product_qty . '" aria-label="Quantity" /></td>';
                        echo '<td>' . $product_name . '</td>';
                        echo '<td>' . peso($product_price) . '</td>';
                        echo '<td>' . peso($subtotal) . '</td>';
                        echo '<td><input type="checkbox" name="remove_code[]" value="' . $product_code . '" /></td>';
                        echo '</tr>';
                        $total = ($total + $subtotal);
                    }
                    ?>
                    <tr>
                        <td colspan="5" class="text-end"><strong>Amount Payable: <?php echo peso($total); ?></strong></td>
                    </tr>
                </tbody>
            </table>
            </div>
            <a href="../index.php" class="btn btn-secondary">Add More Items</a>
            <button type="submit" class="btn btn-outline-primary">Update Cart</button>
        </form>

        <?php
        // VARIABLES: the payment choice typed before, if checkout failed
        $payment = $_SESSION['payMethod'] ?? 'cash';
        $reference = $_SESSION['payReference'] ?? '';
        unset($_SESSION['payMethod'], $_SESSION['payReference']);
        ?>
        <!-- HTML FORM: payment method (cash or online with a reference number), sent to checkout.php -->
        <form method="POST" action="checkout.php" class="card mt-4 payment-card" novalidate>
            <div class="card-header">Payment</div>
            <!-- CSS ONLY: the radio buttons sit next to the reference box so style.css can show it when "Online payment" is checked -->
            <div class="card-body payment-options">
                <input class="form-check-input" type="radio" name="payment_method" id="payCash" value="cash" <?php if ($payment === 'cash') echo 'checked'; ?>>
                <label class="form-check-label" for="payCash">Cash on delivery</label>
                <br>
                <input class="form-check-input" type="radio" name="payment_method" id="payOnline" value="online" <?php if ($payment === 'online') echo 'checked'; ?>>
                <label class="form-check-label" for="payOnline">Online payment (e.g. GCash, Maya, bank transfer)</label>
                <div id="referenceBox" class="reference-box">
                    <label for="reference" class="form-label">Reference number</label>
                    <input type="text" class="form-control" id="reference" name="reference_no" placeholder="e.g. 1012345678901" value="<?php echo htmlspecialchars($reference); ?>">
                    <small class="text-muted">The reference number from your online payment receipt (6 to 30 letters or numbers)</small>
                </div>
                <button type="submit" class="btn btn-primary mt-3 d-block" name="checkout">Checkout</button>
            </div>
        </form>
    <?php
    }
    ?>
</div>
<?php
include('../includes/footer.php');
?>
