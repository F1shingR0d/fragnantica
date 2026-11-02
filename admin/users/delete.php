<?php
// SESSION + INCLUDES: start the session, connect, admin only
session_start();
include('../../includes/config.php');
include('../../includes/adminCheck.php');

// IF blocks: stop if the id is invalid or it is your own account
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id) {
    header('Location: index.php');
    exit;
}
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['message'] = 'you cannot delete your own account';
    header('Location: index.php');
    exit;
}

// TRY-CATCH block + prepared DELETE: blocked when the user has orders or reviews
try {
    $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $_SESSION['success'] = 'user deleted';
} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = 'This user has orders or reviews, so the account cannot be deleted. Deactivate it instead.';
}

header('Location: index.php');
exit;
