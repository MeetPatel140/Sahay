<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$amount = floatval($_POST['amount'] ?? 0);

if ($amount < 10) {
    echo json_encode(['success' => false, 'message' => 'Minimum amount is ₹10']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
$stmt->bind_param("di", $amount, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Money added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add money']);
}

$stmt->close();
$conn->close();
?>
