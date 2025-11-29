<?php
include '../config/db_connect.php';

if ($_POST) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];
    
    // Check if phone already exists
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
    $check_stmt->bind_param("s", $phone);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: ../index.php?error=phone_exists");
        exit;
    }
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (phone, password_hash, full_name, user_type, is_verified) VALUES (?, ?, ?, ?, TRUE)");
    $stmt->bind_param("ssss", $phone, $password, $full_name, $user_type);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // If helper, create helper profile
        if ($user_type === 'helper') {
            $skill_tags = $_POST['skill_tags'];
            $base_rate = $_POST['base_rate'];
            
            $helper_stmt = $conn->prepare("INSERT INTO helper_profiles (user_id, skill_tags, base_rate, current_lat, current_lng, is_available) VALUES (?, ?, ?, 0, 0, FALSE)");
            $helper_stmt->bind_param("isd", $user_id, $skill_tags, $base_rate);
            $helper_stmt->execute();
        }
        
        header("Location: ../index.php?registered=1");
    } else {
        header("Location: ../index.php?error=registration_failed");
    }
}
?>