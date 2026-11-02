<?php
// SESSION + INCLUDES: start the session, connect to the database, show the navbar
session_start();
include("../includes/config.php");
include("../includes/header.php");
?>
<div class="container">
    <div class="auth-card">
        <h2>Create an Account</h2>
        <?php include("../includes/alert.php"); ?>
        <!-- HTML FORM: registration with an optional photo, sent to store.php -->
        <form action="store.php" method="POST" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">name</label>
                <input type="text" class="form-control" id="name" name="name"
                    value="<?php if (isset($_SESSION['regName'])) echo htmlspecialchars($_SESSION['regName']); ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">email</label>
                <input type="text" class="form-control" id="email" name="email"
                    value="<?php if (isset($_SESSION['regEmail'])) echo htmlspecialchars($_SESSION['regEmail']); ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">password</label>
                <input type="password" class="form-control" id="password" name="password">
                <small class="text-muted">at least 6 characters</small>
            </div>

            <div class="mb-3">
                <label for="password2" class="form-label">confirm password</label>
                <input type="password" class="form-control" id="password2" name="confirmPass">
            </div>

            <div class="mb-3">
                <label for="photo" class="form-label">profile photo (optional, JPG or PNG)</label>
                <input type="file" class="form-control" id="photo" name="photo">
            </div>

            <button type="submit" class="btn btn-primary w-100" name="submit">Register</button>
            <p class="mt-3 mb-0 text-center">Already have an account? <a href="login.php">Login</a></p>
        </form>
    </div>
</div>

<?php
// UNSET: forget the typed name and email after showing them
unset($_SESSION['regName'], $_SESSION['regEmail']);
include("../includes/footer.php");
?>
