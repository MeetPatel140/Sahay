<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include 'config/db_connect.php';

$active_mode = $_SESSION['active_mode'] ?? 'customer';

// Get user info
$stmt = $conn->prepare("SELECT full_name, user_type, wallet_balance FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahay Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4 max-w-4xl">
        
        <!-- Header -->
        <div class="bg-white p-4 rounded-lg shadow mb-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-blue-600">Sahay</h1>
                <p class="text-gray-600">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <div class="text-sm text-gray-600">Wallet Balance</div>
                    <div class="font-bold text-green-600">₹<?php echo number_format($user['wallet_balance'], 2); ?></div>
                </div>
                <a href="api/logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
            </div>
        </div>
        
        <!-- Mode Switch (Only for helpers) -->
        <?php if ($user['user_type'] === 'helper'): ?>
        <div class="mode-switcher mb-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" id="mode-toggle" onchange="toggleMode(this.checked)" 
                    <?php echo ($active_mode === 'helper') ? 'checked' : ''; ?> class="mr-3">
                <span class="font-semibold text-lg">
                    <?php echo ($active_mode === 'helper') ? '🟢 Helping Mode (Live)' : '🔵 Customer Mode'; ?>
                </span>
            </label>
            <p class="text-sm mt-1 opacity-90">
                <?php echo ($active_mode === 'helper') ? 'You are visible to customers needing help' : 'Switch to helping mode to earn money'; ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Customer View -->
        <?php if ($active_mode === 'customer'): ?>
        <div id="customer-view">
            <div class="bg-white p-6 rounded-lg shadow mb-4">
                <h2 class="text-xl font-bold mb-4">What help do you need today?</h2>
                
                <form id="taskForm" onsubmit="postTask(event)">
                    <div class="mb-4">
                        <textarea id="task_input" name="description" placeholder="Describe your task in detail..." 
                                 class="w-full p-3 border rounded-lg h-24" required></textarea>
                        <button type="button" onclick="startListening()" class="mt-2 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            🎤 Voice Input
                        </button>
                        <span id="status-text" class="ml-2 text-sm text-gray-600"></span>
                    </div>
                    
                    <div class="mb-4">
                        <input type="number" name="budget" placeholder="Your budget (₹)" 
                               class="w-full p-3 border rounded-lg" required>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">
                        Post Task & Find Helpers
                    </button>
                </form>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow mb-4">
                <h3 class="font-bold mb-3">📍 Your Location & Nearby Helpers</h3>
                <div id="map" style="height: 300px;" class="mb-4 rounded-lg"></div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div id="helpers-list">
                    <h3 class="font-bold mb-3">Available Helpers Nearby:</h3>
                    <div class="text-gray-500">Loading helpers...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Helper View -->
        <?php if ($active_mode === 'helper'): ?>
        <div id="helper-view">
            <div class="bg-white p-6 rounded-lg shadow mb-4">
                <h2 class="text-xl font-bold mb-4">🔴 You are LIVE - Waiting for tasks...</h2>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-green-800">Your location is being shared with customers who need help nearby.</p>
                    <button onclick="updateLocation()" class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        📍 Update My Location
                    </button>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="font-bold mb-3">Available Tasks Near You:</h3>
                <div id="tasks-list">
                    <div class="text-gray-500">Loading tasks...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- My Tasks Section -->
        <div class="bg-white p-6 rounded-lg shadow mt-4">
            <h3 class="font-bold mb-3">My Recent Tasks</h3>
            <div id="my-tasks-list">
                <div class="text-gray-500">Loading your tasks...</div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/map_logic.js"></script>
    <script src="assets/js/voice_logic.js"></script>
</body>
</html>