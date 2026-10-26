<?php
// sets up the database on a new computer: imports database.sql with one click
session_start();
$setupPage = true;
include('./includes/config.php');

$problem = databaseProblem($conn, $db_name);

// the button only works when the database needs to be set up, so the store data cannot be reset by accident
if (isset($_POST['install']) && $problem !== '') {
    try {
        importDatabase($conn);
    } catch (mysqli_sql_exception $e) {
        $_SESSION['message'] = 'could not import database.sql: ' . $e->getMessage();
        header("Location: setup.php");
        exit();
    }
    // the old login is not in the new database
    $_SESSION = array();
    $_SESSION['success'] = 'the database is ready. you can login with admin@veloura.test / admin123 or customer@veloura.test / customer123';
    header("Location: user/login.php");
    exit();
}

include('./includes/header.php');
?>
<div class="container mt-4" style="max-width: 720px;">
    <?php include('./includes/alert.php'); ?>
    <div class="card">
        <div class="card-header">Database setup</div>
        <div class="card-body">
            <?php if ($problem === '') { ?>
                <p>The database <strong><?php echo $db_name; ?></strong> is ready.</p>
                <a class="btn btn-primary" href="index.php">Go to the store</a>
            <?php } else { ?>
                <p><?php echo $problem; ?></p>
                <p>Click the button to import <code>database.sql</code>. It makes the tables, the views and the sample products,
                    with the accounts <code>admin@veloura.test</code> / <code>admin123</code> and <code>customer@veloura.test</code> / <code>customer123</code>.</p>
                <p class="text-danger small">If there is an old copy of the database, its tables are deleted first.</p>
                <form action="setup.php" method="POST">
                    <button class="btn btn-primary" type="submit" name="install">Set up the database</button>
                </form>
            <?php } ?>
        </div>
    </div>
</div>
<?php
include('./includes/footer.php');
?>
