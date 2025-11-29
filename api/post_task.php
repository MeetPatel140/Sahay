<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_POST) {
    $customer_id = $_SESSION['user_id'];
    $description = $_POST['description'];
    $agreed_price = $_POST['budget'];
    $pickup_lat = $_POST['lat'] ?? 0;
    $pickup_lng = $_POST['lng'] ?? 0;
    
    $stmt = $conn->prepare("INSERT INTO tasks (customer_id, description, agreed_price, pickup_lat, pickup_lng, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isddd", $customer_id, $description, $agreed_price, $pickup_lat, $pickup_lng);
    
    if ($stmt->execute()) {
        $task_id = $conn->insert_id;
        echo json_encode(['success' => true, 'task_id' => $task_id, 'message' => 'Task posted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post task']);
    }
}
?>