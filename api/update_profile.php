<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config/db_connect.php';

$full_name = trim($_POST['full_name'] ?? '');
$skill_tags = trim($_POST['skill_tags'] ?? '');
$base_rate = floatval($_POST['base_rate'] ?? 0);

if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
$stmt->bind_param("si", $full_name, $_SESSION['user_id']);
$stmt->execute();

$stmt = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['user_type'] === 'helper' && !empty($skill_tags) && $base_rate > 0) {
    $stmt = $conn->prepare("UPDATE helper_profiles SET skill_tags = ?, base_rate = ? WHERE user_id = ?");
    $stmt->bind_param("sdi", $skill_tags, $base_rate, $_SESSION['user_id']);
    $stmt->execute();
}

echo json_encode(['success' => true, 'message' => 'Profile updated']);

$stmt->close();
$conn->close();
?>
