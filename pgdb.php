<?php
// connect.php — จัดการเชื่อมต่อฐานข้อมูล
$host = 'localhost';
$port = '5432';
$dbname = 'apimodel';
$user = 'panulaosuwan';
$password = 'P@ssw0rd';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $db = new PDO($dsn, $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
