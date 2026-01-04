<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "tooth_talks";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
