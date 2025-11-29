<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$notifications = [];

$stmt = $conn->prepare("SELECT t.task_id, t.description, t.status, t.created_at, u.full_name 
                        FROM tasks t 
                        JOIN users u ON t.customer_id = u.user_id 
                        WHERE t.helper_id = ? AND t.status IN ('accepted', 'in_progress') 
                        ORDER BY t.created_at DESC 
                        LIMIT 10");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'type' => 'task_update',
        'message' => "Task from {$row['full_name']} is {$row['status']}",
        'task_id' => $row['task_id'],
        'created_at' => $row['created_at']
    ];
}

$stmt = $conn->prepare("SELECT t.task_id, t.description, t.status, t.created_at, u.full_name 
                        FROM tasks t 
                        LEFT JOIN users u ON t.helper_id = u.user_id 
                        WHERE t.customer_id = ? AND t.status IN ('accepted', 'in_progress', 'completed') 
                        ORDER BY t.created_at DESC 
                        LIMIT 10");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $helper_name = $row['full_name'] ?? 'Helper';
    $notifications[] = [
        'type' => 'task_update',
        'message' => "{$helper_name} {$row['status']} your task",
        'task_id' => $row['task_id'],
        'created_at' => $row['created_at']
    ];
}

usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode(['success' => true, 'notifications' => array_slice($notifications, 0, 10)]);

$stmt->close();
$conn->close();
?>
