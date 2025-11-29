<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_POST) {
    $task_id = $_POST['task_id'];
    $helper_id = $_SESSION['user_id'];
    
    // Check if task is still available
    $check_stmt = $conn->prepare("SELECT status FROM tasks WHERE task_id = ? AND status = 'pending'");
    $check_stmt->bind_param("i", $task_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Accept the task
        $stmt = $conn->prepare("UPDATE tasks SET helper_id = ?, status = 'accepted' WHERE task_id = ?");
        $stmt->bind_param("ii", $helper_id, $task_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Task accepted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to accept task']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Task no longer available']);
    }
}
?>