<?php
$servername = "localhost";
$username = "root";
$password = "P@ssw0rd"; // ถ้ามีให้ใส่รหัสผ่าน
$dbname = "apimodel"; // ชื่อฐานข้อมูล

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>