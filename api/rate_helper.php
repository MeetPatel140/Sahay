<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$task_id = intval($_POST['task_id'] ?? 0);
$rating = floatval($_POST['rating'] ?? 0);

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, helper_id, status FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task || $task['customer_id'] != $_SESSION['user_id'] || $task['status'] !== 'completed') {
    echo json_encode(['success' => false, 'message' => 'Cannot rate this task']);
    exit;
}

$stmt = $conn->prepare("SELECT profile_id, rating, total_jobs FROM helper_profiles WHERE user_id = ?");
$stmt->bind_param("i", $task['helper_id']);
$stmt->execute();
$helper = $stmt->get_result()->fetch_assoc();

$new_rating = (($helper['rating'] * $helper['total_jobs']) + $rating) / ($helper['total_jobs'] + 1);

$stmt = $conn->prepare("UPDATE helper_profiles SET rating = ?, total_jobs = total_jobs + 1 WHERE user_id = ?");
$stmt->bind_param("di", $new_rating, $task['helper_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Rating submitted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit rating']);
}

$stmt->close();
$conn->close();
?>
