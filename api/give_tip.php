<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$task_id = intval($_POST['task_id'] ?? 0);
$tip_amount = floatval($_POST['tip_amount'] ?? 0);

if ($tip_amount < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid tip amount']);
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, helper_id FROM tasks WHERE task_id = ? AND status = 'completed'");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task not found or not completed']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ? AND wallet_balance >= ?");
    $stmt->bind_param("did", $tip_amount, $_SESSION['user_id'], $tip_amount);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Insufficient balance');
    }
    
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
    $stmt->bind_param("di", $tip_amount, $task['helper_id']);
    $stmt->execute();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Tip sent successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
