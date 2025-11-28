<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$new_mode = $_POST['mode'] ?? 'customer';

if ($new_mode === 'customer' || $new_mode === 'helper') {
    $_SESSION['active_mode'] = $new_mode;
    
    if ($new_mode === 'customer') {
        include '../config/db_connect.php';
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("UPDATE helper_profiles SET is_available = FALSE WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'mode' => $new_mode]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid mode']);
}
?>