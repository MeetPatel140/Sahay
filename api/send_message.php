<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$task_id = intval($_POST['task_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, helper_id FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task || ($task['customer_id'] != $_SESSION['user_id'] && $task['helper_id'] != $_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO task_messages (task_id, sender_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $task_id, $_SESSION['user_id'], $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message sent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}

$stmt->close();
$conn->close();
?>
