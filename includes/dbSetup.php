<?php
// FUNCTION: sends the visitor to setup.php when the database is missing or old (checked once per session)
function checkDatabase($conn, $db_name)
{
    $version = filemtime(__DIR__ . '/../database.sql');
    if (isset($_SESSION['dbChecked']) && $_SESSION['dbChecked'] === $version) {
        return;
    }
    if (databaseProblem($conn, $db_name) !== '') {
        header("Location: " . BASE_URL . "/setup.php");
        exit();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['dbChecked'] = $version;
    }
}

// FUNCTION: compares the real database with database.sql, returns what is wrong or '' if nothing
function databaseProblem($conn, $db_name)
{
    $result = mysqli_execute_query($conn, "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?", [$db_name]);
    if (mysqli_num_rows($result) === 0) {
        return "The database {$db_name} does not exist yet.";
    }

    // WHILE loop: collect the tables and columns that are in the database now
    $found = array();
    $result = mysqli_execute_query($conn, "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?", [$db_name]);
    while ($row = mysqli_fetch_assoc($result)) {
        $found[strtolower($row['TABLE_NAME'])][] = strtolower($row['COLUMN_NAME']);
    }

    // FOREACH loop: check every table and column that database.sql makes
    $sql = file_get_contents(__DIR__ . '/../database.sql');
    preg_match_all('/CREATE TABLE (\w+) \((.*?)\R\);/s', $sql, $tables, PREG_SET_ORDER);
    $notColumns = array('PRIMARY', 'FOREIGN', 'UNIQUE', 'KEY', 'INDEX', 'CONSTRAINT', 'CHECK');
    foreach ($tables as $table) {
        $name = strtolower($table[1]);
        if (!isset($found[$name])) {
            return "The table {$name} is missing.";
        }
        foreach (preg_split('/\R/', $table[2]) as $line) {
            if (preg_match('/^\s*(\w+)\s/', $line, $match) && !in_array(strtoupper($match[1]), $notColumns)) {
                if (!in_array(strtolower($match[1]), $found[$name])) {
                    return "The table {$name} has no {$match[1]} column, so this is an old copy of the database.";
                }
            }
        }
    }

    // FOREACH loop + TRY-CATCH: check that each view exists and works
    preg_match_all('/CREATE VIEW (\w+)/', $sql, $views);
    foreach ($views[1] as $view) {
        try {
            mysqli_query($conn, "SELECT * FROM `{$db_name}`.`{$view}` LIMIT 0");
        } catch (mysqli_sql_exception $e) {
            return "The view {$view} is missing or does not work.";
        }
    }
    return '';
}

// FUNCTION: runs every SQL statement in database.sql (used by setup.php)
function importDatabase($conn)
{
    $sql = file_get_contents(__DIR__ . '/../database.sql');
    mysqli_multi_query($conn, $sql);
    // DO-WHILE loop: go through the result of each statement
    do {
        $result = mysqli_store_result($conn);
        if ($result) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
}
