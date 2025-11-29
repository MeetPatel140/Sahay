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

if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

if ($task['customer_id'] != $_SESSION['user_id'] && $task['helper_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if ($task['status'] === 'completed' || $task['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel this task']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE tasks SET status = 'cancelled' WHERE task_id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    
    if ($task['status'] === 'accepted' || $task['status'] === 'in_progress') {
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
        $stmt->bind_param("di", $task['agreed_price'], $task['customer_id']);
        $stmt->execute();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Task cancelled']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel task']);
}

$stmt->close();
$conn->close();
?>
