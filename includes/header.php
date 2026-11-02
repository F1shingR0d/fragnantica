<!DOCTYPE html>
<html lang="en">

<!-- HTML HEAD: Bootstrap (CSS only, no JavaScript), Google Fonts, Font Awesome icons and our style.css -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="<?= BASE_URL ?>/includes/style/style.css" rel="stylesheet" type="text/css">
    <title>Veloura</title>
</head>

<body>
    <!-- NAVBAR: menu links, search box and login/logout -->
    <nav class="navbar navbar-expand-xl site-nav">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">Veloura</a>
            <!-- CSS-ONLY MENU BUTTON: the hidden checkbox opens and closes the menu on small screens -->
            <input type="checkbox" id="navToggle" class="nav-toggle">
            <label for="navToggle" class="navbar-toggler" aria-label="Open the menu">
                <span class="navbar-toggler-icon"></span>
            </label>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-xl-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php">Home</a>
                    </li>
                    <?php
                    // IF block: menu links for admins only
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                        echo '<li class="nav-item"><a class="nav-link" href="' . BASE_URL . '/product/index.php">Products</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="' . BASE_URL . '/admin/orders.php">Orders</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="' . BASE_URL . '/admin/users/index.php">Users</a></li>';
                    }

                    // IF block: menu links for logged-in users
                    if (isset($_SESSION['user_id'])) {
                        echo '<li class="nav-item"><a class="nav-link" href="' . BASE_URL . '/cart/view_cart.php"><i class="fa-solid fa-bag-shopping"></i> Cart</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="' . BASE_URL . '/user/myorders.php">My Orders</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="' . BASE_URL . '/user/profile.php">My Profile</a></li>';
                    }
                    ?>
                </ul>
                <!-- HTML FORM: search box in the navbar -->
                <form action="<?= BASE_URL ?>/index.php" method="GET" class="d-flex nav-search">
                    <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"
                        name="search">
                    <button class="btn btn-outline-dark" type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <?php
                // IF-ELSE block: Login/Register links, or the user's photo, email and Logout
                if (!isset($_SESSION['user_id'])) {
                    echo "<div class='navbar-nav ms-xl-3'>
                        <a href='" . BASE_URL . "/user/login.php' class='nav-item nav-link'>Login</a>
                        <a href='" . BASE_URL . "/user/register.php' class='nav-item nav-link'>Register</a></div>";
                } else {
                    $photo = "<i class='fa-solid fa-user'></i>";
                    if (!empty($_SESSION['photo'])) {
                        $photo = "<img src='" . BASE_URL . "/user/{$_SESSION['photo']}' class='nav-photo' alt=''>";
                    }
                    $navEmail = htmlspecialchars($_SESSION['email']);
                    echo "<div class='navbar-nav ms-xl-3 align-items-xl-center'>
                        <span class='nav-item nav-link nav-user' title='{$navEmail}'>{$photo} <span class='d-xl-none'>{$navEmail}</span></span>
                        <a href='" . BASE_URL . "/user/logout.php' class='nav-item nav-link'>Logout</a></div>";
                }
                ?>
            </div>
        </div>
    </nav>
