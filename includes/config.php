<?php
// VARIABLES: database login settings (XAMPP default is root with no password)
$db_host = "localhost";
$db_username = "root";
$db_passwd = "";
$db_name = "fragrance_store";
$db_port = 3306;

// BASE_URL: works out the project folder address (e.g. /Veloura) so links work in any folder name
$root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$script = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME']));
$base = '';
if ($script !== '' && strpos($script, $root) === 0) {
    $base = substr($_SERVER['SCRIPT_NAME'], 0, strlen($_SERVER['SCRIPT_NAME']) - (strlen($script) - strlen($root)));
}
define('BASE_URL', $base);

// ERROR SETTING: database errors become exceptions so try-catch blocks can catch them
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// IF block + FUNCTION: adds mysqli_execute_query() (prepared statement shortcut) for PHP older than 8.2
if (!function_exists('mysqli_execute_query')) {
    function mysqli_execute_query($conn, $sql, $params = [])
    {
        $stmt = mysqli_prepare($conn, $sql);
        if (count($params) > 0) {
            mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result === false ? true : $result;
    }
}

// TRY-CATCH block: connect to MySQL, show a message if MySQL is not started
try {
    $conn = mysqli_connect($db_host, $db_username, $db_passwd, '', $db_port);
} catch (mysqli_sql_exception $e) {
    die("Could not connect to MySQL. Open the XAMPP Control Panel and start MySQL, then reload this page.");
}

// INCLUDE + IF block: check the database and select it (skipped on setup.php)
include_once(__DIR__ . '/dbSetup.php');
if (!isset($setupPage)) {
    checkDatabase($conn, $db_name);
    mysqli_select_db($conn, $db_name);
}
