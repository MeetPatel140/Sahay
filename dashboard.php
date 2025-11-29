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
    <title>Sahay - सहाय</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'teal': {
                            500: '#00796B',
                            600: '#00695C'
                        },
                        'orange': {
                            400: '#FFB74D',
                            500: '#FF9800'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Location Alert -->
    <div id="location-alert" class="hidden fixed top-0 left-0 right-0 bg-orange-500 text-white p-3 text-center z-50">
        <i class="fas fa-location-arrow mr-2"></i>
        Please enable location access for better experience.
        <button onclick="checkLocationPermission()" class="ml-3 underline">Enable Now</button>
    </div>
    
    <div class="container mx-auto p-4 max-w-md">
        
        <!-- Header -->
        <div class="bg-white p-4 rounded-2xl shadow-xl mb-4">
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-bars text-gray-600"></i>
                    <h1 class="text-xl font-bold text-teal-600">SAHAY</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-teal-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                        ₹<?php echo number_format($user['wallet_balance'], 0); ?>
                    </div>
                    <button onclick="document.getElementById('menu').classList.toggle('hidden')" class="text-gray-600">
                        <i class="fas fa-user-circle text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="menu" class="hidden mt-3 pt-3 border-t">
                <p class="text-sm text-gray-600 mb-2">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></p>
                <a href="api/logout.php" class="text-red-500 text-sm"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
            </div>
        </div>
        
        <!-- Mode Switch (Only for helpers) -->
        <?php if ($user['user_type'] === 'helper'): ?>
        <div class="bg-gradient-to-r from-teal-500 to-teal-600 p-4 rounded-2xl shadow-xl mb-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold text-lg">
                        <?php echo ($active_mode === 'helper') ? 'Helping Mode' : 'Customer Mode'; ?>
                    </div>
                    <div class="text-sm opacity-90">
                        <?php echo ($active_mode === 'helper') ? 'You are LIVE and visible' : 'Switch to earn money'; ?>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="mode-toggle" onchange="toggleMode(this.checked)" 
                        <?php echo ($active_mode === 'helper') ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="w-14 h-8 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-orange-500"></div>
                </label>
            </div>
        </div>
        <?php endif; ?>

        <!-- Customer View -->
        <?php if ($active_mode === 'customer'): ?>
        <div id="customer-view">
            <!-- Map Background -->
            <div class="relative mb-4">
                <div id="map" style="height: 400px;" class="rounded-2xl shadow-xl"></div>
                
                <!-- Floating Input Card -->
                <div class="absolute bottom-4 left-4 right-4 bg-white p-6 rounded-2xl shadow-2xl">
                    <h2 class="text-xl font-bold text-center mb-4 text-gray-800">What help do you need?</h2>
                    <p class="text-center text-gray-500 text-sm mb-4">"Bol kar madad maange"</p>
                    
                    <form id="taskForm" onsubmit="postTask(event)">
                        <div class="flex items-center justify-center mb-4">
                            <button type="button" onclick="startListening()" 
                                class="w-20 h-20 bg-orange-400 hover:bg-orange-500 text-white rounded-full shadow-xl transition-all duration-300 hover:scale-105">
                                <i class="fas fa-microphone text-2xl"></i>
                            </button>
                        </div>
                        <span id="status-text" class="block text-center text-sm text-gray-600 mb-3"></span>
                        
                        <textarea id="task_input" name="description" placeholder="या यहाँ टाइप करें..." 
                                 class="w-full p-3 border border-gray-200 rounded-xl mb-3 text-sm" rows="2" required></textarea>
                        
                        <div class="flex space-x-3">
                            <input type="number" name="budget" placeholder="Budget (₹)" 
                                   class="flex-1 p-3 border border-gray-200 rounded-xl text-sm" required>
                            <button type="submit" class="bg-teal-500 text-white px-6 py-3 rounded-xl hover:bg-teal-600 transition-colors">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-2xl shadow-xl">
                <div id="helpers-list">
                    <h3 class="font-bold mb-3 text-gray-800"><i class="fas fa-users text-teal-500 mr-2"></i>Available Helpers Nearby:</h3>
                    <div class="text-gray-500 text-center py-4">Loading helpers...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Helper View -->
        <?php if ($active_mode === 'helper'): ?>
        <div id="helper-view">
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-2xl shadow-xl mb-4 text-white">
                <div class="text-center">
                    <div class="w-4 h-4 bg-red-500 rounded-full mx-auto mb-2 animate-pulse"></div>
                    <h2 class="text-xl font-bold mb-2">Aap Live Hain</h2>
                    <p class="text-sm opacity-90 mb-3">Customers can see you and send tasks</p>
                    <button onclick="updateLocation()" class="bg-white/20 text-white px-4 py-2 rounded-full text-sm hover:bg-white/30 transition-colors">
                        <i class="fas fa-location-arrow mr-1"></i> Update Location
                    </button>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-2xl shadow-xl">
                <h3 class="font-bold mb-3 text-gray-800"><i class="fas fa-tasks text-orange-500 mr-2"></i>Available Tasks Near You:</h3>
                <div id="tasks-list">
                    <div class="text-gray-500 text-center py-8">
                        <i class="fas fa-search text-3xl mb-2 opacity-50"></i>
                        <div>Looking for tasks...</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- My Tasks Section -->
        <div class="bg-white p-4 rounded-2xl shadow-xl mt-4">
            <h3 class="font-bold mb-3 text-gray-800"><i class="fas fa-history text-gray-500 mr-2"></i>My Recent Tasks</h3>
            <div id="my-tasks-list">
                <div class="text-gray-500 text-center py-4">Loading your tasks...</div>
            </div>
        </div>
    </div>
    
    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-2 max-w-md mx-auto">
        <div class="flex justify-around">
            <button class="p-3 text-teal-500">
                <i class="fas fa-home text-xl"></i>
            </button>
            <button class="p-3 text-gray-400">
                <i class="fas fa-search text-xl"></i>
            </button>
            <button class="p-3 text-gray-400">
                <i class="fas fa-bell text-xl"></i>
            </button>
            <button class="p-3 text-gray-400">
                <i class="fas fa-user text-xl"></i>
            </button>
        </div>
    </div>
    
    <div class="h-20"></div> <!-- Spacer for bottom nav -->

    <script src="assets/js/app.js"></script>
    <script src="assets/js/map_logic.js"></script>
    <script src="assets/js/voice_logic.js"></script>
</body>
</html>