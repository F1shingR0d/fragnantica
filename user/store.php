<?php
// SESSION + INCLUDES: start the session, connect to the database, load functions
session_start();
include("../includes/config.php");
include("../includes/functions.php");

// IF block: only the registration form can open this script
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

// VARIABLES: read the form and remember the typed name and email
$name = trim(strip_tags($_POST['name']));
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$confirmPass = trim($_POST['confirmPass']);
$_SESSION['regName'] = $name;
$_SESSION['regEmail'] = $email;

// IF-ELSE IF blocks (validation): name, email format, password length, matching passwords
if ($name === '') {
    $_SESSION['message'] = 'please enter your name';
    header("Location: register.php");
    exit();
} else if (!isValidEmail($email)) {
    $_SESSION['message'] = 'email invalid format';
    header("Location: register.php");
    exit();
} else if (!(strlen($password) >= 6)) {
    $_SESSION['message'] = 'password should be at least 6 characters';
    header("Location: register.php");
    exit();
} else if ($password !== $confirmPass) {
    $_SESSION['message'] = 'passwords do not match';
    header("Location: register.php");
    exit();
}

// IF block + SELECT query: stop if the email is already registered
$result = mysqli_execute_query($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1", [$email]);
if (mysqli_num_rows($result) > 0) {
    $_SESSION['message'] = 'this email is already registered';
    header("Location: register.php");
    exit();
}

// IF block: save the optional profile photo if it is a JPG or PNG
$target = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if (isImage($_FILES['photo']['type'])) {
        $target = savePhoto($_FILES['photo']['tmp_name'], $_FILES['photo']['name']);
    } else {
        $_SESSION['message'] = 'wrong file type, please use JPG or PNG';
        header("Location: register.php");
        exit();
    }
}

// TRY-CATCH block + prepared INSERT: hash the password and save the new user
try {
    $password = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (name, email, password, img_path, role, status, created_at) VALUES (?, ?, ?, ?, 'customer', 'active', NOW())";
    $stmt1 = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt1, 'ssss', $name, $email, $password, $target);
    mysqli_stmt_execute($stmt1);

    unset($_SESSION['regName'], $_SESSION['regEmail']);
    $_SESSION['success'] = 'registration successful, you can now login';
    header("Location: login.php");
} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = 'registration failed, please try again';
    header("Location: register.php");
}
