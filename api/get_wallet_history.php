<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$stmt = $conn->prepare("SELECT transaction_id, amount, transaction_type, description, created_at 
                        FROM wallet_transactions 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 50");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode(['success' => true, 'transactions' => $transactions]);

$stmt->close();
$conn->close();
?>
