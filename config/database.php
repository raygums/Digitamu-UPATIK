<?php
$host   = "localhost";
$port   = "5432";
$dbname = "bukutamu";
$user   = "postgres";
$pass   = "123";

$connection_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$pass}";

$db = pg_connect($connection_string);

if (!$db) {
    die("Koneksi error: " . pg_last_error()); 
}

// Fungsi helper untuk query
function query($sql, $params = []) {
    global $db;
    if (empty($params)) {
        $result = pg_query($db, $sql);
    } else {
        $result = pg_query_params($db, $sql, $params);
    }
    return $result;
}

// Fungsi helper untuk fetch all
function fetchAll($result) {
    if (!$result) return [];
    return pg_fetch_all($result) ?: [];
}

// Fungsi helper untuk fetch one
function fetchOne($result) {
    if (!$result) return null;
    return pg_fetch_assoc($result);
}

// Fungsi helper untuk escape string
function escape($string) {
    global $db;
    return pg_escape_string($db, $string);
}

// Fungsi helper untuk last insert id (PostgreSQL)
function lastInsertId($table, $column = 'id') {
    global $db;
    $result = pg_query($db, "SELECT currval(pg_get_serial_sequence('{$table}', '{$column}'))");
    $row = pg_fetch_row($result);
    return $row[0] ?? null;
}
?>