<?php
// SESSION + INCLUDES: start the session, connect to the database, load functions
session_start();
include("../includes/config.php");
include("../includes/functions.php");

// IF block (form submitted): read the email and password
if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $hasErrors = false;

    // IF-ELSE blocks (validation): check the fields in PHP
    if ($email === '') {
        $_SESSION['emailError'] = 'Please enter your email';
        $hasErrors = true;
    } else if (!isValidEmail($email)) {
        $_SESSION['emailError'] = 'Please enter a valid email, e.g. juan@gmail.com';
        $hasErrors = true;
    }
    if ($password === '') {
        $_SESSION['passwordError'] = 'Please enter your password';
        $hasErrors = true;
    }

    // IF block + prepared SELECT: find the user by email
    if (!$hasErrors) {
        $sql = "SELECT user_id, email, password, img_path, role, status FROM users WHERE email = ? LIMIT 1";
        $result = mysqli_execute_query($conn, $sql, [$email]);

        if ($result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);
            // IF-ELSE block: wrong password, deactivated account, or log in and save the user in the session
            if (!password_verify($password, $row['password'])) {
                $_SESSION['message'] = 'wrong email or password';
            } else if ($row['status'] !== 'active') {
                $_SESSION['message'] = 'your account has been deactivated';
            } else {
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['photo'] = $row['img_path'];
                if ($row['role'] === 'admin') {
                    header("Location: ../product/index.php");
                } else {
                    header("Location: ../index.php");
                }
                exit();
            }
        } else {
            $_SESSION['message'] = 'wrong email or password';
        }
    }
}

include("../includes/header.php");
?>
<div class="container">
<div class="auth-card">
    <h2>Welcome back</h2>
    <?php include("../includes/alert.php"); ?>
    <!-- HTML FORM: login form, novalidate so only PHP checks it -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" novalidate>
        <div class="form-outline mb-4">
            <label class="form-label" for="email">Email address</label>
            <input type="text" id="email" class="form-control" name="email"
                value="<?php if (isset($email)) echo htmlspecialchars($email); ?>" />
            <small class="text-danger"><?php
                // IF block: show the email error under the field
                if (isset($_SESSION['emailError'])) {
                    echo $_SESSION['emailError'];
                    unset($_SESSION['emailError']);
                }
                ?></small>
        </div>

        <div class="form-outline mb-4">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" class="form-control" name="password" />
            <small class="text-danger"><?php
                // IF block: show the password error under the field
                if (isset($_SESSION['passwordError'])) {
                    echo $_SESSION['passwordError'];
                    unset($_SESSION['passwordError']);
                }
                ?></small>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-4" name="submit">Sign in</button>

        <div class="text-center">
            <p class="mb-0">Not a member? <a href="register.php">Register</a></p>
        </div>
    </form>
</div>
</div>
<?php
include("../includes/footer.php");
?>
