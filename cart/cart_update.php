<?php
// SESSION + INCLUDES: start the session, connect, require login
session_start();
include('../includes/config.php');
include('../includes/loginCheck.php');

// IF block (Add to Cart): add a product to the session cart
if (isset($_POST["type"]) && $_POST["type"] == 'add') {
    $product_id = (int)$_POST['product_id'];
    $qty = trim($_POST['product_qty']);

    // SELECT query: get the product and its stock
    $sql = "SELECT p.product_id, p.name, p.sell_price, s.quantity FROM product p INNER JOIN stock s USING (product_id) WHERE p.product_id = ?";
    $row = mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$product_id]));

    // IF blocks (validation): the product exists and the quantity is a whole number of 1 or more
    if (!$row) {
        $_SESSION['message'] = 'product not found';
        header('Location: ../index.php');
        exit();
    }
    if (filter_var($qty, FILTER_VALIDATE_INT) === false || $qty < 1) {
        $_SESSION['message'] = 'please enter a valid quantity';
        header('Location: ../index.php');
        exit();
    }

    // IF blocks: add to the quantity already in the cart, then check the stock
    if (isset($_SESSION["cart_products"][$product_id])) {
        $qty = $qty + $_SESSION["cart_products"][$product_id]["product_qty"];
    }
    if ($qty > $row['quantity']) {
        $_SESSION['message'] = "only {$row['quantity']} {$row['name']} left in stock";
        header('Location: ../index.php');
        exit();
    }

    // ARRAY: save the product in the session cart
    $new_product = array();
    $new_product["product_id"] = $row['product_id'];
    $new_product["product_name"] = $row['name'];
    $new_product["product_price"] = $row['sell_price'];
    $new_product["product_qty"] = (int)$qty;
    $_SESSION["cart_products"][$product_id] = $new_product;

    $_SESSION['success'] = "{$row['name']} added to your cart";
    header('Location: view_cart.php');
    exit();
}

// IF block (Update button): change quantities or remove products
if (isset($_POST["product_qty"]) || isset($_POST["remove_code"])) {
    if (isset($_POST["product_qty"]) && is_array($_POST["product_qty"])) {
        // FOREACH loop: set each new quantity
        foreach ($_POST["product_qty"] as $key => $value) {
            if (!isset($_SESSION["cart_products"][$key])) {
                continue;
            }
            if (filter_var($value, FILTER_VALIDATE_INT) !== false && $value > 0) {
                $_SESSION["cart_products"][$key]["product_qty"] = (int)$value;
            } else {
                $_SESSION['message'] = 'please enter a valid quantity';
            }
        }
    }

    if (isset($_POST["remove_code"]) && is_array($_POST["remove_code"])) {
        // FOREACH loop: remove the ticked products
        foreach ($_POST["remove_code"] as $key) {
            unset($_SESSION["cart_products"][$key]);
        }
    }
}

header('Location: view_cart.php');
