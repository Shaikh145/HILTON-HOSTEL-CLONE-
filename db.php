<?php
// Database connection
$host = "localhost";
$dbname = "dbvrvj4zswfp2q";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}
?>
