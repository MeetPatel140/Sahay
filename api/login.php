<?php
session_start();
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php?error=1");
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($phone) || empty($password)) {
    header("Location: ../index.php?error=1");
    exit;
}

$stmt = $conn->prepare("SELECT user_id, password_hash, user_type FROM users WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['active_mode'] = 'customer';
        
        header("Location: ../dashboard.php");
        exit;
    }
}

header("Location: ../index.php?error=1");
$stmt->close();
$conn->close();
?>