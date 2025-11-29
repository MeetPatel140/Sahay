<?php
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php?error=1");
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? '';
$skill_tags = trim($_POST['skill_tags'] ?? '');
$base_rate = floatval($_POST['base_rate'] ?? 0);

if (empty($full_name) || empty($phone) || empty($password) || empty($user_type)) {
    header("Location: ../index.php?error=1");
    exit;
}

if ($user_type === 'helper' && (empty($skill_tags) || $base_rate <= 0)) {
    header("Location: ../index.php?error=1");
    exit;
}

$check_stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
$check_stmt->bind_param("s", $phone);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    header("Location: ../index.php?error=1");
    exit;
}

$conn->begin_transaction();

try {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (phone, password_hash, full_name, user_type, wallet_balance) VALUES (?, ?, ?, ?, 500.00)");
    $stmt->bind_param("ssss", $phone, $password_hash, $full_name, $user_type);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        if ($user_type === 'helper') {
            $helper_stmt = $conn->prepare("INSERT INTO helper_profiles (user_id, skill_tags, base_rate, is_available) VALUES (?, ?, ?, FALSE)");
            $helper_stmt->bind_param("isd", $user_id, $skill_tags, $base_rate);
            $helper_stmt->execute();
        }
        
        $conn->commit();
        header("Location: ../index.php?registered=1");
    } else {
        $conn->rollback();
        header("Location: ../index.php?error=1");
    }
} catch (Exception $e) {
    $conn->rollback();
    header("Location: ../index.php?error=1");
}

$conn->close();
?>