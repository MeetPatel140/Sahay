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
$radius_km = 10;

if ($user_lat == 0 || $user_lng == 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT u.user_id, u.full_name, hp.skill_tags, hp.base_rate, hp.rating, hp.total_jobs,
       ( 6371 * acos( cos( radians(?) ) * cos( radians( hp.current_lat ) ) 
       * cos( radians( hp.current_lng ) - radians(?) ) + sin( radians(?) ) 
       * sin( radians( hp.current_lat ) ) ) ) AS distance 
       FROM helper_profiles hp
       JOIN users u ON hp.user_id = u.user_id
       WHERE hp.is_available = TRUE AND hp.current_lat != 0 AND hp.current_lng != 0
       HAVING distance < ? 
       ORDER BY distance ASC 
       LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("dddd", $user_lat, $user_lng, $user_lat, $radius_km);
$stmt->execute();
$result = $stmt->get_result();

$helpers = [];
while($row = $result->fetch_assoc()) {
    $helpers[] = $row;
}

echo json_encode($helpers);
$stmt->close();
$conn->close();
?>