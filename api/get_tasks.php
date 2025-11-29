<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_lat = $_GET['lat'] ?? 0;
$user_lng = $_GET['lng'] ?? 0;
$radius_km = 10;

// Get pending tasks near helper's location
$sql = "SELECT t.task_id, t.description, t.agreed_price, t.created_at, u.full_name as customer_name,
       ( 6371 * acos( cos( radians($user_lat) ) * cos( radians( t.pickup_lat ) ) 
       * cos( radians( t.pickup_lng ) - radians($user_lng) ) + sin( radians($user_lat) ) 
       * sin( radians( t.pickup_lat ) ) ) ) AS distance 
       FROM tasks t
       JOIN users u ON t.customer_id = u.user_id
       WHERE t.status = 'pending' AND t.customer_id != {$_SESSION['user_id']}
       HAVING distance < $radius_km 
       ORDER BY t.created_at DESC 
       LIMIT 20";

$result = $conn->query($sql);

$tasks = [];
while($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode(['success' => true, 'tasks' => $tasks]);
?>