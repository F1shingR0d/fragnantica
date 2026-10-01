<?php
session_start();
include('../includes/config.php');

// only admins can add perfumes
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'admin access only, please log in with an admin account';
    header("Location: ../user/login.php");
    exit();
}

if (isset($_POST['submit'])) {
    $name = trim(strip_tags($_POST['name']));
    $brand = trim(strip_tags($_POST['brand']));
    $scent = trim($_POST['scent_type']);
    $sizeInput = trim($_POST['size_ml']);
    $costInput = trim($_POST['cost_price']);
    $sellInput = trim($_POST['sell_price']);
    $qtyInput = trim($_POST['quantity']);
    $hasErrors = false;

    //validation
    if ($name === '') {
        $_SESSION['nameError'] = 'Please input the perfume name';
        $hasErrors = true;
    }
    if ($brand === '') {
        $_SESSION['brandError'] = 'Please input the brand';
        $hasErrors = true;
    }
    if ($scent === '') {
        $_SESSION['scentError'] = 'Please choose a scent type';
        $hasErrors = true;
    }
    if ($sizeInput === '' || filter_var($sizeInput, FILTER_VALIDATE_INT) === false || (int)$sizeInput <= 0) {
        $_SESSION['sizeError'] = 'Please enter a valid size in ml';
        $hasErrors = true;
    }
    if ($costInput === '' || !is_numeric($costInput) || $costInput <= 0) {
        $_SESSION['costError'] = 'Please enter a valid cost price';
        $hasErrors = true;
    }
    if ($sellInput === '' || !is_numeric($sellInput) || $sellInput <= 0) {
        $_SESSION['sellError'] = 'Please enter a valid selling price';
        $hasErrors = true;
    }
    if ($qtyInput === '' || filter_var($qtyInput, FILTER_VALIDATE_INT) === false || (int)$qtyInput < 0) {
        $_SESSION['qtyError'] = 'Please enter a valid stock quantity';
        $hasErrors = true;
    }

    // photo is required, and only JPG and PNG are accepted
    if (!isset($_FILES['img_path']) || $_FILES['img_path']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['imageError'] = 'Please select a photo';
        $hasErrors = true;
    } else if ($_FILES['img_path']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['imageError'] = 'Photo upload failed, please try again';
        $hasErrors = true;
    } else if ($_FILES['img_path']['type'] != "image/jpeg" && $_FILES['img_path']['type'] != "image/jpg" && $_FILES['img_path']['type'] != "image/png") {
        $_SESSION['imageError'] = 'Only JPG and PNG photos are accepted';
        $hasErrors = true;
    }

    if ($hasErrors) {
        // keep what the admin typed so the form can be filled again
        $_SESSION['perfumeName'] = $name;
        $_SESSION['brand'] = $brand;
        $_SESSION['scent'] = $scent;
        $_SESSION['size'] = $sizeInput;
        $_SESSION['cost'] = $costInput;
        $_SESSION['sell'] = $sellInput;
        $_SESSION['qty'] = $qtyInput;
        header("Location: create.php");
        exit();
    }

    $size = (int)$sizeInput;
    $cost = (float)$costInput;
    $sell = (float)$sellInput;
    $qty = (int)$qtyInput;

    // save the photo in the images folder with a new name so files do not overwrite each other
    if ($_FILES['img_path']['type'] == "image/png") {
        $ext = '.png';
    } else {
        $ext = '.jpg';
    }
    $source = $_FILES['img_path']['tmp_name'];
    $target = 'images/' . time() . $ext;
    move_uploaded_file($source, $target) or die("Couldn't copy");

    // save the perfume and its stock together
    mysqli_begin_transaction($conn);

    try {
        $sql = "INSERT INTO perfume (name, brand, scent_type, size_ml, cost_price, sell_price, img_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt1, 'sssidds', $name, $brand, $scent, $size, $cost, $sell, $target);
        mysqli_stmt_execute($stmt1);
        $perfume_id = mysqli_insert_id($conn);

        $sql = "INSERT INTO stock (perfume_id, quantity) VALUES (?, ?)";
        $stmt2 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt2, 'ii', $perfume_id, $qty);
        mysqli_stmt_execute($stmt2);

        mysqli_commit($conn);
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = 'Could not save the perfume: ' . $e->getMessage();
        header("Location: create.php");
        exit();
    }

    // clear the saved form values
    unset($_SESSION['perfumeName'], $_SESSION['brand'], $_SESSION['scent'], $_SESSION['size'], $_SESSION['cost'], $_SESSION['sell'], $_SESSION['qty']);

    $_SESSION['success'] = 'Perfume added successfully';
    header("Location: index.php");
    exit();
}

header("Location: create.php");
exit();
