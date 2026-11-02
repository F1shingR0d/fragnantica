<?php

// IF block: show the error message (red) once, then remove it
if (isset($_SESSION['message'])) {

    echo "<div class='alert alert-danger' role='alert'>
    <i class='fa-solid fa-circle-exclamation me-2'></i>{$_SESSION['message']}</div>";
    unset($_SESSION['message']);
}

// IF block: show the success message (green) once, then remove it
if (isset($_SESSION['success'])) {

    echo "<div class='alert alert-success' role='alert'>
    <i class='fa-solid fa-circle-check me-2'></i>{$_SESSION['success']}</div>";
    unset($_SESSION['success']);
}
