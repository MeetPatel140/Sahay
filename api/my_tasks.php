<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get tasks where user is customer or helper
$sql = "SELECT t.task_id, t.description, t.agreed_price, t.status, t.created_at,
               CASE 
                   WHEN t.customer_id = ? THEN 'customer'
                   WHEN t.helper_id = ? THEN 'helper'
               END as my_role,
               CASE 
                   WHEN t.customer_id = ? THEN h.full_name
                   WHEN t.helper_id = ? THEN c.full_name
               END as other_person
        FROM tasks t
        LEFT JOIN users c ON t.customer_id = c.user_id
        LEFT JOIN users h ON t.helper_id = h.user_id
        WHERE t.customer_id = ? OR t.helper_id = ?
        ORDER BY t.created_at DESC
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode(['success' => true, 'tasks' => $tasks]);
?>