<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include 'config/db_connect.php';

$active_mode = $_SESSION['active_mode'] ?? 'customer';

$stmt = $conn->prepare("SELECT full_name, user_type, wallet_balance FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#00796B">
    <title>Sahayak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="overflow-hidden">

    <div id="location-alert" class="hidden fixed top-0 left-0 right-0 bg-orange-500 text-white p-2 text-center z-50 text-sm">
        <i class="fas fa-location-arrow mr-1"></i>Enable location
        <button onclick="checkLocationPermission()" class="ml-2 underline font-medium">Enable</button>
    </div>
    
    <div id="map" class="absolute inset-0 z-0"></div>
    
    <div class="fixed top-0 left-0 right-0 z-40 p-4">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <button onclick="openMenu()" class="w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center">
                <i class="fas fa-bars text-gray-700"></i>
            </button>
            
            <?php if ($user['user_type'] === 'helper'): ?>
            <div class="flex items-center bg-white rounded-full shadow-lg px-4 py-2">
                <span class="text-sm font-medium mr-3 <?php echo $active_mode === 'customer' ? 'text-teal-600' : 'text-gray-400'; ?>">SEEK</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="mode-toggle" onchange="toggleMode(this.checked)" 
                        <?php echo ($active_mode === 'helper') ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-teal-600 peer-checked:after:translate-x-5 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                </label>
                <span class="text-sm font-medium ml-3 <?php echo $active_mode === 'helper' ? 'text-teal-600' : 'text-gray-400'; ?>">HELP</span>
            </div>
            <?php endif; ?>
            
            <button onclick="addMoney()" class="px-4 py-2 bg-teal-600 text-white rounded-full shadow-lg text-sm font-bold">
                ₹<?php echo number_format($user['wallet_balance'], 0); ?>
            </button>
        </div>
    </div>
    
    <div id="menu" class="hidden fixed top-0 left-0 w-64 h-full bg-white shadow-2xl z-50 p-6">
        <button onclick="closeMenu()" class="absolute top-4 right-4 text-gray-500">
            <i class="fas fa-times text-xl"></i>
        </button>
        <div class="mt-8">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-teal-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="ml-3">
                    <p class="font-bold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo ucfirst($user['user_type']); ?></p>
                </div>
            </div>
            <div class="space-y-4">
                <button onclick="openSettings()" class="w-full text-left flex items-center text-gray-700 hover:text-teal-600">
                    <i class="fas fa-cog w-6"></i>
                    <span class="ml-3">Settings</span>
                </button>
                <button onclick="document.getElementById('my-tasks-panel').classList.remove('hidden')" class="w-full text-left flex items-center text-gray-700 hover:text-teal-600">
                    <i class="fas fa-history w-6"></i>
                    <span class="ml-3">My Tasks</span>
                </button>
                <a href="api/logout.php" class="w-full text-left flex items-center text-red-500 hover:text-red-600">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="ml-3">Logout</span>
                </a>
            </div>
        </div>
    </div>

    <?php if ($active_mode === 'customer'): ?>
    <div id="customer-view" class="relative z-10">
        <div class="fixed bottom-0 left-0 right-0 z-30 p-4">
            <div class="max-w-md mx-auto bg-white rounded-3xl shadow-2xl p-6">
                <h2 class="text-2xl font-bold text-center mb-2 text-gray-900">What help do you need?</h2>
                <p class="text-center text-gray-400 text-sm mb-6">"Bol kar madad maange"</p>
                
                <form id="taskForm" onsubmit="postTask(event)">
                    <div class="flex justify-center mb-6">
                        <button type="button" onclick="startListening()" 
                            class="w-24 h-24 bg-gradient-to-br from-orange-400 to-orange-500 text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-105 transition-transform">
                            <i class="fas fa-microphone text-3xl"></i>
                        </button>
                    </div>
                    <span id="status-text" class="block text-center text-sm text-gray-500 mb-4"></span>
                    
                    <textarea id="task_input" name="description" placeholder="Type your need here..." 
                             class="w-full p-4 border-2 border-gray-100 rounded-2xl mb-4 text-sm focus:border-teal-500 focus:outline-none" rows="2" required></textarea>
                    
                    <div class="flex gap-3">
                        <input type="number" name="budget" placeholder="Budget ₹" 
                               class="flex-1 p-4 border-2 border-gray-100 rounded-2xl text-sm focus:border-teal-500 focus:outline-none" required>
                        <button type="submit" class="bg-teal-600 text-white px-8 py-4 rounded-2xl hover:bg-teal-700 transition-colors font-medium">
                            <i class="fas fa-search"></i> Find
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="helpers-panel" class="hidden fixed bottom-0 left-0 right-0 z-40 bg-white rounded-t-3xl shadow-2xl max-h-96 overflow-y-auto">
            <div class="max-w-md mx-auto p-6">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <div id="helpers-list"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($active_mode === 'helper'): ?>
    <div id="helper-view" class="relative z-10">
        <div class="fixed top-20 left-0 right-0 z-30 px-4">
            <div class="max-w-md mx-auto bg-gradient-to-r from-green-500 to-green-600 p-4 rounded-3xl shadow-2xl text-white text-center">
                <div class="flex items-center justify-center mb-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse mr-2"></div>
                    <span class="font-bold text-lg">You are LIVE</span>
                </div>
                <p class="text-sm opacity-90">Customers can see you</p>
            </div>
        </div>
        
        <div class="fixed bottom-0 left-0 right-0 z-30 p-4">
            <div class="max-w-md mx-auto bg-white rounded-3xl shadow-2xl p-6 max-h-96 overflow-y-auto">
                <h3 class="font-bold mb-4 text-gray-900 flex items-center">
                    <i class="fas fa-tasks text-orange-500 mr-2"></i>Available Tasks
                </h3>
                <div id="tasks-list">
                    <div class="text-gray-400 text-center py-8">
                        <i class="fas fa-search text-4xl mb-3 opacity-30"></i>
                        <div class="text-sm">Looking for tasks...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="my-tasks-panel" class="hidden fixed inset-0 bg-black/50 z-50" onclick="this.classList.add('hidden')">
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl max-h-96 overflow-y-auto" onclick="event.stopPropagation()">
            <div class="max-w-md mx-auto p-6">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="font-bold mb-4 text-gray-900"><i class="fas fa-history text-gray-400 mr-2"></i>My Tasks</h3>
                <div id="my-tasks-list"></div>
            </div>
        </div>
    </div>

    <div id="settings-panel" class="hidden fixed inset-0 bg-black/50 z-50" onclick="closeSettings()">
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl" onclick="event.stopPropagation()">
            <div class="max-w-md mx-auto p-6">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="font-bold mb-4 text-gray-900"><i class="fas fa-cog text-gray-400 mr-2"></i>Settings</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600 mb-2 block">App Language</label>
                        <select onchange="changeLanguage(this.value)" class="w-full p-3 border-2 border-gray-100 rounded-xl focus:border-teal-500 focus:outline-none">
                            <option value="en-IN">English (India)</option>
                            <option value="hi-IN">हिंदी (Hindi)</option>
                            <option value="bn-IN">বাংলা (Bengali)</option>
                            <option value="te-IN">తెలుగు (Telugu)</option>
                            <option value="ta-IN">தமிழ் (Tamil)</option>
                            <option value="mr-IN">मराठी (Marathi)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="wallet-panel" class="hidden fixed inset-0 bg-black/50 z-50" onclick="closeWallet()">
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl" onclick="event.stopPropagation()">
            <div class="max-w-md mx-auto p-6">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="font-bold mb-4 text-gray-900"><i class="fas fa-wallet text-teal-600 mr-2"></i>Add Money</h3>
                <form onsubmit="processAddMoney(event)">
                    <input type="number" id="add-amount" placeholder="Enter amount" min="10" 
                           class="w-full p-4 border-2 border-gray-100 rounded-2xl mb-4 focus:border-teal-500 focus:outline-none" required>
                    <button type="submit" class="w-full bg-teal-600 text-white p-4 rounded-2xl font-medium">
                        Add to Wallet
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/map_logic.js"></script>
    <script src="assets/js/voice_logic.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
