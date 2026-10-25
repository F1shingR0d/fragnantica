<?php
session_start();
include('../includes/config.php');
include('../includes/functions.php');

// 1. Must be logged in as customer
require_customer();

$user_id = $_SESSION['user_id'];
$methods = payment_methods();

// the items total of the customer's cart, at today's prices
function cart_items_total($conn, $user_id)
{
    $sql = "SELECT COALESCE(SUM(c.quantity * p.sell_price), 0) AS total FROM cart c INNER JOIN product p USING (product_id) WHERE c.user_id = ?";
    return (float)mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$user_id]))['total'];
}

// Apply or remove a coupon (POST). The code is kept in the session until the order is placed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['apply_coupon']) || isset($_POST['remove_coupon']))) {
    if (!csrf_valid()) {
        $_SESSION['message'] = 'Your session expired. Please try again.';
    } else if (isset($_POST['remove_coupon'])) {
        unset($_SESSION['coupon_code']);
        $_SESSION['success'] = 'The coupon was removed.';
    } else {
        $code = normalize_coupon_code(post_value('coupon_code'));
        if ($code === '') {
            keep_form('coupon', array('coupon_code' => 'Please enter a coupon code.'), array('coupon_code' => ''));
        } else {
            $coupon = find_coupon($conn, $code);
            $error = coupon_error($conn, $coupon, $user_id, cart_items_total($conn, $user_id));
            if ($error !== '') {
                keep_form('coupon', array('coupon_code' => $error), array('coupon_code' => $code));
            } else {
                $_SESSION['coupon_code'] = $coupon['code'];
                $_SESSION['success'] = 'Coupon ' . e($coupon['code']) . ' applied: ' . coupon_label($coupon) . '.';
            }
        }
    }
    header("Location: checkout.php");
    exit();
}

// Handle order placement (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!csrf_valid()) {
        $_SESSION['message'] = 'Your session expired. Please review your order and try again.';
        header("Location: checkout.php");
        exit();
    }

    // the payment method must be one we actually offer
    $payment_method = isset($_POST['payment_method']) ? (string)$_POST['payment_method'] : '';
    if (!isset($methods[$payment_method])) {
        $_SESSION['message'] = 'Please choose a payment method.';
        header("Location: checkout.php");
        exit();
    }

    // Verify customer delivery details
    $sql = "SELECT addressline, town, zipcode, phone FROM customer WHERE user_id = ? LIMIT 1";
    $result = mysqli_execute_query($conn, $sql, [$user_id]);
    $customer = mysqli_fetch_assoc($result);

    if (!$customer || empty(trim($customer['addressline'])) || empty(trim($customer['town'])) || empty(trim($customer['zipcode'])) || empty(trim($customer['phone']))) {
        $_SESSION['message'] = 'Please complete your delivery address in your profile before checking out.';
        header("Location: ../user/profile.php");
        exit();
    }

    $shipping_address = "{$customer['addressline']}, {$customer['town']}, {$customer['zipcode']} (Phone: {$customer['phone']})";

    // BEGIN TRANSACTION
    mysqli_begin_transaction($conn);

    try {
        // 1. Fetch current cart items
        $sql = "SELECT c.product_id, c.quantity, p.name, p.sell_price, p.cost_price
                FROM cart c
                INNER JOIN product p USING (product_id)
                WHERE c.user_id = ?";
        $result = mysqli_execute_query($conn, $sql, [$user_id]);
        $items = mysqli_fetch_all($result, MYSQLI_ASSOC);

        if (empty($items)) {
            mysqli_rollback($conn);
            $_SESSION['message'] = 'Your cart is empty.';
            header("Location: index.php");
            exit();
        }

        $grandTotal = 0;

        // 2. Lock each stock row with SELECT ... FOR UPDATE and verify availability
        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $requestedQty = (int)$item['quantity'];

            $lockSql = "SELECT quantity FROM stock WHERE product_id = ? FOR UPDATE";
            $lockResult = mysqli_execute_query($conn, $lockSql, [$product_id]);
            $stockRow = mysqli_fetch_assoc($lockResult);

            $currentStock = $stockRow ? (int)$stockRow['quantity'] : 0;

            if ($currentStock < $requestedQty) {
                mysqli_rollback($conn);
                $safeName = e($item['name']);
                $_SESSION['message'] = "Not enough stock for {$safeName}. Only {$currentStock} remaining.";
                header("Location: index.php");
                exit();
            }

            $grandTotal += $item['sell_price'] * $requestedQty;
        }
        $itemsTotal = $grandTotal;

        // 3. Check the coupon again (the cart or the coupon may have changed since it was applied).
        //    Its row is locked, so a coupon with limited uses cannot be used more times than allowed.
        $coupon_id = null;
        $discount = 0.0;
        if (!empty($_SESSION['coupon_code'])) {
            $coupon = find_coupon($conn, $_SESSION['coupon_code'], true);
            $error = coupon_error($conn, $coupon, $user_id, $itemsTotal);
            if ($error !== '') {
                mysqli_rollback($conn);
                unset($_SESSION['coupon_code']);
                $_SESSION['message'] = 'Your coupon was removed: ' . $error . ' Please review your order and place it again.';
                header("Location: checkout.php");
                exit();
            }
            $coupon_id = (int)$coupon['coupon_id'];
            $discount = coupon_discount($coupon, $itemsTotal);
            $grandTotal = $itemsTotal - $discount;
        }

        // 4. Create the order. It starts as pending and unpaid; the admin moves it along from there.
        $orderSql = "INSERT INTO orders (user_id, total_amount, coupon_id, discount_amount, status, shipping_address, payment_method, payment_status, created_at)
                     VALUES (?, ?, ?, ?, 'pending', ?, ?, 'unpaid', NOW())";
        mysqli_execute_query($conn, $orderSql, [$user_id, $grandTotal, $coupon_id, $discount, $shipping_address, $payment_method]);
        $order_id = mysqli_insert_id($conn);

        // 5. Insert order_items and deduct stock
        $itemInsertSql = "INSERT INTO order_items (order_id, product_id, quantity, price, cost) VALUES (?, ?, ?, ?, ?)";
        $itemInsertStmt = mysqli_prepare($conn, $itemInsertSql);

        $deductStockSql = "UPDATE stock SET quantity = quantity - ? WHERE product_id = ?";
        $deductStockStmt = mysqli_prepare($conn, $deductStockSql);

        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['quantity'];
            $price = (float)$item['sell_price'];
            $cost = (float)$item['cost_price'];

            mysqli_stmt_bind_param($itemInsertStmt, 'iiidd', $order_id, $product_id, $quantity, $price, $cost);
            mysqli_stmt_execute($itemInsertStmt);

            mysqli_stmt_bind_param($deductStockStmt, 'ii', $quantity, $product_id);
            mysqli_stmt_execute($deductStockStmt);
        }

        // 6. First line of the order's history
        $placedNote = 'Order placed (' . payment_label($payment_method) . ')';
        if ($coupon_id !== null) {
            $placedNote .= ' with coupon ' . $coupon['code'];
        }
        record_status($conn, $order_id, 'pending', $placedNote, $user_id);

        // 7. Empty the customer's cart
        $clearCartSql = "DELETE FROM cart WHERE user_id = ?";
        mysqli_execute_query($conn, $clearCartSql, [$user_id]);

        // 8. COMMIT the transaction
        mysqli_commit($conn);
        unset($_SESSION['coupon_code']);
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = 'Checkout failed due to a database error. Please try again.';
        header("Location: index.php");
        exit();
    }

    // 9. the order is saved; now email the customer (a failed email does not undo the order)
    notify_order_placed($conn, $order_id);

    $_SESSION['success'] = "Order #{$order_id} placed successfully! A confirmation has been sent to your email.";
    header("Location: order_confirmation.php?id={$order_id}");
    exit();
}

// GET: Display checkout review page
$sql = "SELECT c.product_id, c.quantity, p.name, p.brand, p.size, p.unit, p.sell_price, p.img_path,
               s.quantity AS stock
        FROM cart c
        INNER JOIN product p USING (product_id)
        INNER JOIN stock s USING (product_id)
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC";
$result = mysqli_execute_query($conn, $sql, [$user_id]);
$cartItems = mysqli_fetch_all($result, MYSQLI_ASSOC);

if (empty($cartItems)) {
    $_SESSION['message'] = 'Your cart is empty.';
    header("Location: index.php");
    exit();
}

// Fetch customer delivery details
$customerSql = "SELECT u.name, u.email, c.addressline, c.town, c.zipcode, c.phone
                FROM users u
                LEFT JOIN customer c USING (user_id)
                WHERE u.user_id = ? LIMIT 1";
$customerResult = mysqli_execute_query($conn, $customerSql, [$user_id]);
$customer = mysqli_fetch_assoc($customerResult);

$hasDeliveryDetails = !empty($customer['addressline']) && !empty($customer['town']) && !empty($customer['zipcode']) && !empty($customer['phone']);

$hasStockIssue = false;
$grandTotal = 0;
foreach ($cartItems as $item) {
    $grandTotal += $item['sell_price'] * $item['quantity'];
    if ($item['quantity'] > $item['stock']) {
        $hasStockIssue = true;
    }
}
$itemsTotal = $grandTotal;

// the applied coupon, checked again because the cart may have changed since it was applied
$appliedCoupon = null;
$discount = 0.0;
if (!empty($_SESSION['coupon_code'])) {
    $coupon = find_coupon($conn, $_SESSION['coupon_code']);
    $couponError = coupon_error($conn, $coupon, $user_id, $itemsTotal);
    if ($couponError !== '') {
        unset($_SESSION['coupon_code']);
        $_SESSION['message'] = 'Your coupon was removed: ' . $couponError;
    } else {
        $appliedCoupon = $coupon;
        $discount = coupon_discount($coupon, $itemsTotal);
        $grandTotal = $itemsTotal - $discount;
    }
}
list($couponErrors, $couponOld) = take_form('coupon');

include('../includes/header.php');
?>
<div class="container mt-4">
    <?php include('../includes/alert.php'); ?>
    <h2>Checkout Review</h2>

    <div class="row mt-4">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Order Summary</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cartItems as $item):
                            $lineTotal = $item['sell_price'] * $item['quantity'];
                            $stockWarning = $item['quantity'] > $item['stock']
                                ? "<br><small class='text-danger'>Only " . (int)$item['stock'] . " available in stock</small>" : '';
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../product/<?= e($item['img_path']) ?>" width="50" height="50" class="me-2 rounded product-photo" alt="<?= e($item['name']) ?>">
                                        <div>
                                            <strong><?= e($item['name']) ?></strong><br>
                                            <small class="text-muted"><?= e($item['brand']) ?> &middot; <?= (int)$item['size'] ?> <?= e($item['unit']) ?></small>
                                            <?= $stockWarning ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= peso($item['sell_price']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td><?= peso($lineTotal) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <?php if ($appliedCoupon): ?>
                                <tr>
                                    <td colspan="3" class="text-end">Items total:</td>
                                    <td><?= peso($itemsTotal) ?></td>
                                </tr>
                                <tr class="text-success">
                                    <td colspan="3" class="text-end">Coupon <strong class="font-monospace"><?= e($appliedCoupon['code']) ?></strong> (<?= coupon_label($appliedCoupon) ?>):</td>
                                    <td>&minus; <?= peso($discount) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th><?= peso($grandTotal) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <a href="index.php" class="btn btn-secondary">&larr; Back to Cart</a>
        </div>

        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Delivery Information</strong>
                    <a href="../user/profile.php" class="btn btn-sm btn-outline-primary">Edit Profile</a>
                </div>
                <div class="card-body">
                    <?php if ($hasDeliveryDetails): ?>
                        <p class="mb-1"><strong>Recipient:</strong> <?= e($customer['name']) ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?= e($customer['email']) ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?= e($customer['phone']) ?></p>
                        <p class="mb-1"><strong>Address:</strong> <?= e($customer['addressline']) ?></p>
                        <p class="mb-0"><strong>Town / Zipcode:</strong> <?= e($customer['town']) ?>, <?= e($customer['zipcode']) ?></p>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Missing Delivery Address!</strong><br>
                            You must fill in your delivery details before placing an order.
                        </div>
                        <a href="../user/profile.php" class="btn btn-primary w-100">
                            Complete Delivery Details
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong><i class="fa-solid fa-ticket me-1"></i> Coupon</strong></div>
                <div class="card-body">
                    <?php if ($appliedCoupon): ?>
                        <form action="checkout.php" method="POST" class="d-flex justify-content-between align-items-center gap-2">
                            <?= csrf_field() ?>
                            <div>
                                <strong class="font-monospace"><?= e($appliedCoupon['code']) ?></strong>
                                <span class="badge bg-success ms-1"><?= coupon_label($appliedCoupon) ?></span>
                                <br><small class="text-muted">You save <?= peso($discount) ?></small>
                            </div>
                            <button type="submit" name="remove_coupon" class="btn btn-sm btn-outline-secondary">Remove</button>
                        </form>
                    <?php else: ?>
                        <form action="checkout.php" method="POST" novalidate>
                            <?= csrf_field() ?>
                            <label for="coupon_code" class="form-label small">Have a coupon code?</label>
                            <div class="input-group">
                                <input type="text" class="form-control text-uppercase font-monospace<?= invalid_class($couponErrors, 'coupon_code') ?>"
                                    id="coupon_code" name="coupon_code" value="<?= e($couponOld['coupon_code'] ?? '') ?>" placeholder="Enter code">
                                <button type="submit" name="apply_coupon" class="btn btn-outline-primary">Apply</button>
                            </div>
                            <?= field_error($couponErrors, 'coupon_code') ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($hasDeliveryDetails): ?>
                <form action="checkout.php" method="POST">
                    <?= csrf_field() ?>
                    <div class="card mb-4">
                        <div class="card-header"><strong>Payment Method</strong></div>
                        <div class="card-body">
                            <?php $first = true; foreach ($methods as $code => $method): ?>
                                <div class="form-check payment-option">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                        id="pay_<?= e($code) ?>" value="<?= e($code) ?>" <?= $first ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="pay_<?= e($code) ?>">
                                        <i class="<?= e($method['icon']) ?> me-1"></i> <strong><?= e($method['label']) ?></strong>
                                        <br><small class="text-muted"><?= e($method['description']) ?></small>
                                    </label>
                                </div>
                            <?php $first = false; endforeach; ?>
                            <p class="small text-muted mb-0 mt-2">Online payment (GCash, cards) is coming soon.</p>
                        </div>
                    </div>

                    <?php if ($hasStockIssue): ?>
                        <div class="alert alert-warning mb-3">
                            Some items exceed available stock. Please adjust quantities in your cart before placing order.
                        </div>
                        <button type="button" class="btn btn-success w-100 btn-lg" disabled>Place Order</button>
                    <?php else: ?>
                        <button type="submit" name="place_order" class="btn btn-success w-100 btn-lg">
                            <i class="fa-solid fa-check"></i> Place Order (<?= peso($grandTotal) ?>)
                        </button>
                        <p class="small text-muted text-center mt-2 mb-0">You can cancel while the order is still pending.</p>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
