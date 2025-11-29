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

$user_id = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // Add to wallet
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
    $stmt->bind_param("di", $amount, $user_id);
    $stmt->execute();
    
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, transaction_type, amount, description) VALUES (?, 'deposit', ?, 'Wallet top-up')");
    $stmt->bind_param("id", $user_id, $amount);
    $stmt->execute();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Money added successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Transaction failed']);
}

$conn->close();
?>
