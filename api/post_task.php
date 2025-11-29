<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$description = trim($_POST['description'] ?? '');
$agreed_price = floatval($_POST['budget'] ?? 0);
$pickup_lat = floatval($_POST['lat'] ?? 0);
$pickup_lng = floatval($_POST['lng'] ?? 0);

if (empty($description) || $agreed_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task details']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO tasks (customer_id, description, agreed_price, pickup_lat, pickup_lng, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isddd", $customer_id, $description, $agreed_price, $pickup_lat, $pickup_lng);
    
    if ($stmt->execute()) {
        $task_id = $conn->insert_id;
        
        $stmt2 = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ? AND wallet_balance >= ?");
        $stmt2->bind_param("did", $agreed_price, $customer_id, $agreed_price);
        
        if ($stmt2->execute() && $stmt2->affected_rows > 0) {
            $stmt3 = $conn->prepare("INSERT INTO wallet_transactions (user_id, task_id, amount, transaction_type, description) VALUES (?, ?, ?, 'debit', 'Task payment')");
            $stmt3->bind_param("iid", $customer_id, $task_id, $agreed_price);
            $stmt3->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'task_id' => $task_id, 'message' => 'Task posted successfully!']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance']);
        }
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to post task']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>