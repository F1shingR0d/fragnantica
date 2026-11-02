<?php
// INCLUDE (access control): must be logged in with an active account (redirects to the login page with a message)
include(__DIR__ . '/loginCheck.php');

// IF block (access control): logged in but not an admin, go back to the home page
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'Only administrators can access that page';
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
