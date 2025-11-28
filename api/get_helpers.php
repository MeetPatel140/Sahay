<?php
include '../config/db_connect.php';

$user_lat = $_GET['lat'];
$user_lng = $_GET['lng'];
$radius_km = 5;

$sql = "SELECT u.full_name, hp.skill_tags, hp.base_rate, 
       ( 6371 * acos( cos( radians($user_lat) ) * cos( radians( hp.current_lat ) ) 
       * cos( radians( hp.current_lng ) - radians($user_lng) ) + sin( radians($user_lat) ) 
       * sin( radians( hp.current_lat ) ) ) ) AS distance 
       FROM helper_profiles hp
       JOIN users u ON hp.user_id = u.user_id
       WHERE hp.is_available = TRUE
       HAVING distance < $radius_km 
       ORDER BY distance ASC 
       LIMIT 10";

$result = $conn->query($sql);

$helpers = [];
while($row = $result->fetch_assoc()) {
    $helpers[] = $row;
}

echo json_encode($helpers);
?>