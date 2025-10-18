<?php
$servername = "localhost";
$username = "root";
$password = "8oojkiyd"; // ถ้ามีให้ใส่รหัสผ่าน P@ssw0rd
$dbname = "apimodel"; // ชื่อฐานข้อมูล

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>