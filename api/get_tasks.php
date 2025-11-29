<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_lat = floatval($_GET['lat'] ?? 0);
$user_lng = floatval($_GET['lng'] ?? 0);
$radius_km = 15;

if ($user_lat == 0 || $user_lng == 0) {
    echo json_encode(['success' => true, 'tasks' => []]);
    exit;
}

$sql = "SELECT t.task_id, t.description, t.agreed_price, t.created_at, u.full_name as customer_name,
       ( 6371 * acos( cos( radians(?) ) * cos( radians( t.pickup_lat ) ) 
       * cos( radians( t.pickup_lng ) - radians(?) ) + sin( radians(?) ) 
       * sin( radians( t.pickup_lat ) ) ) ) AS distance 
       FROM tasks t
       JOIN users u ON t.customer_id = u.user_id
       WHERE t.status = 'pending' AND t.customer_id != ? AND t.pickup_lat != 0 AND t.pickup_lng != 0
       HAVING distance < ? 
       ORDER BY t.created_at DESC 
       LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("dddid", $user_lat, $user_lng, $user_lat, $_SESSION['user_id'], $radius_km);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode(['success' => true, 'tasks' => $tasks]);
$stmt->close();
$conn->close();
?>