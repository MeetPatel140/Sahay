<?php
$host = "localhost";
$user = "u992693575_sahay";
$pass = "Meetsolanki@6353877251";
$db   = "u992693575_sahay";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>