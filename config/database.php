<?php
$host   = "localhost";
$port   = "5432";
$dbname = "bukutamu";
$user   = "postgres";
$pass   = "123";

$conn   = "host={$host} port={$port} dbname={$dbname} user={$user} password={$pass}";

$db     = pg_connect($connection_string);

if (!db){
    die ("Koneksi error " . pg_last_error()); 
} else {
    echo "Koneksi berhasil";
}
?>