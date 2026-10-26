<?php
// database settings (the XAMPP default is user root with no password)
$db_host = "localhost";
$db_username = "root";
$db_passwd = "";
$db_name = "fragrance_store";
$db_port = 3306;

// the address of the project folder, e.g. /Veloura in http://localhost/Veloura
// it is worked out from where this file is, so the links still work if the folder
// has another name or is copied to another computer
$root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$script = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME']));
$base = '';
if ($script !== '' && strpos($script, $root) === 0) {
    $base = substr($_SERVER['SCRIPT_NAME'], 0, strlen($_SERVER['SCRIPT_NAME']) - (strlen($script) - strlen($root)));
}
define('BASE_URL', $base);

// show database errors as exceptions on every PHP version (PHP 8.0 does not do it by default)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// mysqli_execute_query() only exists in PHP 8.2 and newer, so older XAMPP versions get this copy
if (!function_exists('mysqli_execute_query')) {
    function mysqli_execute_query($conn, $sql, $params = [])
    {
        $stmt = mysqli_prepare($conn, $sql);
        if (count($params) > 0) {
            mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        // INSERT, UPDATE and DELETE have no result
        return $result === false ? true : $result;
    }
}

try {
    $conn = mysqli_connect($db_host, $db_username, $db_passwd, '', $db_port);
} catch (mysqli_sql_exception $e) {
    die("Could not connect to MySQL. Open the XAMPP Control Panel and start MySQL, then reload this page.");
}

// on a new computer (or with an old copy of the database) send the visitor to setup.php,
// which imports database.sql with one click
include_once(__DIR__ . '/dbSetup.php');
if (!isset($setupPage)) {
    checkDatabase($conn, $db_name);
    mysqli_select_db($conn, $db_name);
}
