<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$task_id = intval($_GET['task_id'] ?? 0);

$stmt = $conn->prepare("SELECT customer_id, helper_id FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task || ($task['customer_id'] != $_SESSION['user_id'] && $task['helper_id'] != $_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$stmt = $conn->prepare("SELECT m.message_id, m.sender_id, m.message, m.created_at, u.full_name 
                        FROM task_messages m 
                        JOIN users u ON m.sender_id = u.user_id 
                        WHERE m.task_id = ? 
                        ORDER BY m.created_at ASC");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $row['is_mine'] = ($row['sender_id'] == $_SESSION['user_id']);
    $messages[] = $row;
}

echo json_encode(['success' => true, 'messages' => $messages]);

$stmt->close();
$conn->close();
?>
