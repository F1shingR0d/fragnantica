<?php
// SESSION + INCLUDES: start the session, connect, load functions, admin only
session_start();
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/adminCheck.php');

// IF block (form submitted): read and clean the form fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = trim(strip_tags($_POST['name']));
    $category_id = trim($_POST['category_id']);
    $brand = trim(strip_tags($_POST['brand']));
    $size = trim(strip_tags($_POST['size']));
    $desc = trim(strip_tags($_POST['description']));
    $costInput = trim($_POST['cost_price']);
    $sellInput = trim($_POST['sell_price']);
    $qtyInput = trim($_POST['quantity']);
    $hasErrors = false;

    // IF-ELSE blocks (validation): check every field in PHP
    if ($name === '') {
        $_SESSION['nameError'] = 'Please input the product name';
        $hasErrors = true;
    } else if (strlen($name) > 100) {
        $_SESSION['nameError'] = 'The product name should be 100 characters or less';
        $hasErrors = true;
    }
    if ($category_id === '' || !is_numeric($category_id)) {
        $_SESSION['categoryError'] = 'Please choose a category';
        $hasErrors = true;
    }
    if ($brand === '') {
        $_SESSION['brandError'] = 'Please input the brand';
        $hasErrors = true;
    }
    if ($size === '') {
        $_SESSION['sizeError'] = 'Please input the size, e.g. 100 ml';
        $hasErrors = true;
    }
    if ($desc === '') {
        $_SESSION['descError'] = 'Please input a product description';
        $hasErrors = true;
    } else if (strlen($desc) > 255) {
        $_SESSION['descError'] = 'The description should be 255 characters or less';
        $hasErrors = true;
    }
    if ($costInput === '' || !is_numeric($costInput) || $costInput <= 0) {
        $_SESSION['costError'] = 'Please enter a valid cost price';
        $hasErrors = true;
    }
    if ($sellInput === '' || !is_numeric($sellInput) || $sellInput <= 0) {
        $_SESSION['sellError'] = 'Please enter a valid sell price';
        $hasErrors = true;
    } else if (is_numeric($costInput) && $sellInput < $costInput) {
        $_SESSION['sellError'] = 'The sell price should not be lower than the cost price';
        $hasErrors = true;
    }
    if ($qtyInput === '' || filter_var($qtyInput, FILTER_VALIDATE_INT) === false || (int)$qtyInput < 0) {
        $_SESSION['qtyError'] = 'Please enter a valid quantity';
        $hasErrors = true;
    }

    // IF-ELSE block: the main photo is required and must be JPG or PNG
    if (!isset($_FILES['img_path']) || $_FILES['img_path']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['imageError'] = 'Please select an image';
        $hasErrors = true;
    } else if (!isImage($_FILES['img_path']['type'])) {
        $_SESSION['imageError'] = 'wrong file type, please use JPG or PNG';
        $hasErrors = true;
    }

    // IF block + FOREACH loop: extra photos are optional but must be JPG or PNG
    if (isset($_FILES['photos']) && $_FILES['photos']['name'][0] !== '') {
        foreach ($_FILES['photos']['type'] as $key => $type) {
            if (!isImage($type)) {
                $_SESSION['photosError'] = "wrong file type: {$_FILES['photos']['name'][$key]}, please use JPG or PNG";
                $hasErrors = true;
            }
        }
    }

    // IF block: keep the typed values and go back to the form
    if ($hasErrors) {
        $_SESSION['prodName'] = $name;
        $_SESSION['category'] = $category_id;
        $_SESSION['brand'] = $brand;
        $_SESSION['size'] = $size;
        $_SESSION['desc'] = $desc;
        $_SESSION['cost'] = $costInput;
        $_SESSION['sell'] = $sellInput;
        $_SESSION['qty'] = $qtyInput;
        header("Location: create.php");
        exit();
    }

    // TYPE CASTING: turn the text inputs into numbers
    $cost = (float)$costInput;
    $sell = (float)$sellInput;
    $qty = (int)$qtyInput;
    $category_id = (int)$category_id;

    // FUNCTION CALL: save the main photo
    $target = savePhoto($_FILES['img_path']['tmp_name'], $_FILES['img_path']['name']);

    // TRY-CATCH block (TRANSACTION): save the product, its stock and extra photos together
    mysqli_begin_transaction($conn);
    try {
        // PREPARED STATEMENT (INSERT): the product
        $sql = "INSERT INTO product (category_id, name, brand, size, description, cost_price, sell_price, img_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt1, 'issssdds', $category_id, $name, $brand, $size, $desc, $cost, $sell, $target);
        mysqli_stmt_execute($stmt1);
        $product_id = mysqli_insert_id($conn);

        // PREPARED STATEMENT (INSERT): its stock
        $sql = "INSERT INTO stock (product_id, quantity) VALUES (?, ?)";
        $stmt2 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt2, 'ii', $product_id, $qty);
        mysqli_stmt_execute($stmt2);

        // IF block + FOREACH loop: save each extra photo in product_images
        if (isset($_FILES['photos']) && $_FILES['photos']['name'][0] !== '') {
            $sql = "INSERT INTO product_images (product_id, img_path) VALUES (?, ?)";
            $stmt3 = mysqli_prepare($conn, $sql);
            foreach ($_FILES['photos']['name'] as $key => $photoName) {
                $photo = savePhoto($_FILES['photos']['tmp_name'][$key], $photoName);
                mysqli_stmt_bind_param($stmt3, 'is', $product_id, $photo);
                mysqli_stmt_execute($stmt3);
            }
        }

        mysqli_commit($conn);
        $_SESSION['success'] = 'product saved';
        header("Location: index.php");
        exit();
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = 'could not save the product';
        header("Location: create.php");
        exit();
    }
}
header("Location: create.php");
