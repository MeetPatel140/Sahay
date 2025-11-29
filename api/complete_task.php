<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$task_id = intval($_POST['task_id'] ?? 0);

$stmt = $conn->prepare("SELECT customer_id, helper_id, agreed_price, status FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task || $task['status'] !== 'in_progress') {
    echo json_encode(['success' => false, 'message' => 'Invalid task']);
    exit;
}

if ($task['helper_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE task_id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    
    $commission = $task['agreed_price'] * 0.10;
    $helper_amount = $task['agreed_price'] - $commission;
    
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
    $stmt->bind_param("di", $helper_amount, $task['helper_id']);
    $stmt->execute();
    
    $stmt = $conn->prepare("INSERT INTO wallet_transactions (user_id, task_id, amount, transaction_type, description) VALUES (?, ?, ?, 'credit', 'Task payment received')");
    $stmt->bind_param("iid", $task['helper_id'], $task_id, $helper_amount);
    $stmt->execute();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Task completed']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to complete task']);
}

$stmt->close();
$conn->close();
?>
