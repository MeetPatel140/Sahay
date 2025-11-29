<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$user_id = $_SESSION['user_id'];
$amount = floatval($_POST['amount'] ?? 0);

if ($amount < 10) {
    echo json_encode(['success' => false, 'message' => 'Minimum withdrawal amount is ₹10']);
    exit;
}

// Check weekly withdrawal limit
$week_start = date('Y-m-d', strtotime('monday this week'));
$stmt = $conn->prepare("SELECT COUNT(*) as withdrawal_count FROM transactions WHERE user_id = ? AND transaction_type = 'withdrawal' AND DATE(created_at) >= ?");
$stmt->bind_param("is", $user_id, $week_start);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['withdrawal_count'] >= 3) {
    echo json_encode(['success' => false, 'message' => 'Weekly withdrawal limit (3) exceeded']);
    exit;
}

// Check user balance
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['wallet_balance'] < $amount) {
    echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
    exit;
}

$conn->begin_transaction();

try {
    // Deduct from wallet
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
    $stmt->bind_param("di", $amount, $user_id);
    $stmt->execute();
    
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, transaction_type, amount, description) VALUES (?, 'withdrawal', ?, 'Wallet withdrawal')");
    $stmt->bind_param("id", $user_id, $amount);
    $stmt->execute();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Withdrawal processed successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Transaction failed']);
}
?>