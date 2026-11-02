<?php
// SESSION + INCLUDES: start the session, connect, load functions, admin only
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');
include('../../includes/adminCheck.php');

// IF block: only the Edit User form can open this script
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// VARIABLES: read the form
$user_id = (int)$_POST['user_id'];
$name = trim(strip_tags($_POST['name']));
$email = trim($_POST['email']);
$back = "edit.php?id={$user_id}";

// SELECT query + IF block: load the user, go back if not found
$sql = "SELECT role, status, img_path FROM users WHERE user_id = ? LIMIT 1";
$user = mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$user_id]));
if (!$user) {
    $_SESSION['message'] = 'user not found';
    header("Location: index.php");
    exit();
}

// IF block: keep the current role when the role list was disabled
$role = $user['role'];
if (isset($_POST['role'])) {
    $role = $_POST['role'];
}

// IF-ELSE IF blocks (validation): name, email, role, only active users, not your own role
if ($name === '') {
    $_SESSION['message'] = 'please enter the name';
    header("Location: {$back}");
    exit();
} else if (!isValidEmail($email)) {
    $_SESSION['message'] = 'email invalid format';
    header("Location: {$back}");
    exit();
} else if ($role !== 'customer' && $role !== 'admin') {
    $_SESSION['message'] = 'please choose a role';
    header("Location: {$back}");
    exit();
} else if ($role !== $user['role'] && $user['status'] !== 'active') {
    $_SESSION['message'] = 'only an active user\'s role can be changed';
    header("Location: {$back}");
    exit();
} else if ($role !== $user['role'] && $user_id == $_SESSION['user_id']) {
    $_SESSION['message'] = 'you cannot change your own role';
    header("Location: {$back}");
    exit();
}

// IF block + SELECT query: the email must not be used by another user
$result = mysqli_execute_query($conn, "SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1", [$email, $user_id]);
if (mysqli_num_rows($result) > 0) {
    $_SESSION['message'] = 'this email is already used by another user';
    header("Location: {$back}");
    exit();
}

// IF block: replace the photo if a new JPG or PNG was chosen
$target = $user['img_path'];
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if (isImage($_FILES['photo']['type'])) {
        $target = savePhoto($_FILES['photo']['tmp_name'], $_FILES['photo']['name'], '../../user/');
    } else {
        $_SESSION['message'] = 'wrong file type, please use JPG or PNG';
        header("Location: {$back}");
        exit();
    }
}

// TRY-CATCH block + prepared UPDATE: save the changes
try {
    $sql = "UPDATE users SET name = ?, email = ?, role = ?, img_path = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $role, $target, $user_id);
    mysqli_stmt_execute($stmt);

    // IF block: update the navbar if the admin edited their own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['email'] = $email;
        $_SESSION['photo'] = $target;
    }
    $_SESSION['success'] = 'user updated';
    header("Location: index.php");
} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = 'could not update the user';
    header("Location: {$back}");
}
