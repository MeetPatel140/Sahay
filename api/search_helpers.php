<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$skill = trim($_GET['skill'] ?? '');
$lat = floatval($_GET['lat'] ?? 0);
$lng = floatval($_GET['lng'] ?? 0);
$radius_km = 10;

$sql = "SELECT u.user_id, u.full_name, hp.skill_tags, hp.base_rate, hp.rating, hp.total_jobs,
       ( 6371 * acos( cos( radians(?) ) * cos( radians( hp.current_lat ) ) 
       * cos( radians( hp.current_lng ) - radians(?) ) + sin( radians(?) ) 
       * sin( radians( hp.current_lat ) ) ) ) AS distance 
       FROM helper_profiles hp
       JOIN users u ON hp.user_id = u.user_id
       WHERE hp.is_available = TRUE";

if (!empty($skill)) {
    $sql .= " AND hp.skill_tags LIKE ?";
}

$sql .= " HAVING distance < ? ORDER BY distance ASC LIMIT 20";

$stmt = $conn->prepare($sql);

if (!empty($skill)) {
    $skill_param = "%{$skill}%";
    $stmt->bind_param("dddsd", $lat, $lng, $lat, $skill_param, $radius_km);
} else {
    $stmt->bind_param("dddd", $lat, $lng, $lat, $radius_km);
}

$stmt->execute();
$result = $stmt->get_result();

$helpers = [];
while ($row = $result->fetch_assoc()) {
    $helpers[] = $row;
}

echo json_encode(['success' => true, 'helpers' => $helpers]);

$stmt->close();
$conn->close();
?>
