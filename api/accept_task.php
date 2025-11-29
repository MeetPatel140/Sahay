<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$task_id = intval($_POST['task_id'] ?? 0);
$helper_id = $_SESSION['user_id'];

if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
    exit;
}

$conn->begin_transaction();

try {
    $check_stmt = $conn->prepare("SELECT customer_id, status FROM tasks WHERE task_id = ? AND status = 'pending'");
    $check_stmt->bind_param("i", $task_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $task = $result->fetch_assoc();
        
        if ($task['customer_id'] == $helper_id) {
            echo json_encode(['success' => false, 'message' => 'Cannot accept your own task']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE tasks SET helper_id = ?, status = 'accepted' WHERE task_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $helper_id, $task_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Task accepted successfully!']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Task already taken']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Task not available']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>