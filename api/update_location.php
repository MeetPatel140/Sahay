<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_POST) {
    $user_id = $_SESSION['user_id'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    
    // Update helper location and set available
    $stmt = $conn->prepare("UPDATE helper_profiles SET current_lat = ?, current_lng = ?, is_available = TRUE WHERE user_id = ?");
    $stmt->bind_param("ddi", $lat, $lng, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update location']);
    }
}
?>