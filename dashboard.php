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
    <style>
        .btn-teal { background-color: #00796B; }
        .btn-teal:hover { background-color: #00695C; }
        .text-teal { color: #00796B; }
        .bg-teal { background-color: #00796B; }
    </style>
</head>
<body class="overflow-hidden">

    <div id="location-alert" class="hidden fixed top-0 left-0 right-0 bg-orange-500 text-white p-2 text-center z-50 text-sm">
        <i class="fas fa-location-arrow mr-1"></i>Enable location
        <button type="button" onclick="checkLocationPermission()" class="ml-2 underline font-medium">Enable</button>
    </div>
    
    <div id="map" class="absolute inset-0 z-0"></div>
    
    <div class="fixed top-0 left-0 right-0 z-40 p-4">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <button type="button" onclick="openMenu()" class="w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center">
                <i class="fas fa-bars text-gray-700"></i>
            </button>
            
            <?php if ($user['user_type'] === 'helper'): ?>
            <div class="flex items-center bg-white rounded-full shadow-lg px-3 py-2 gap-2">
                <span class="text-xs font-bold text-teal">SEEK</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="mode-toggle" onchange="toggleMode(this.checked)" 
                        <?php echo ($active_mode === 'helper') ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-teal after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5 shadow-inner"></div>
                </label>
                <span class="text-xs font-bold text-teal">HELP</span>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-full shadow-lg px-4 py-2">
                <span class="text-xs font-bold text-teal">SEEK</span>
            </div>
            <?php endif; ?>
            
            <div class="flex items-center gap-2">
                <button type="button" onclick="openNotifications()" class="relative w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center">
                    <i class="fas fa-bell text-gray-700"></i>
                    <span id="notif-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">0</span>
                </button>
                <button type="button" onclick="openWallet()" class="px-3 py-2 bg-teal text-white rounded-full shadow-lg text-xs font-bold flex items-center gap-1">
                    <i class="fas fa-wallet"></i>
                    ₹<?php echo number_format($user['wallet_balance'], 0); ?>
                </button>
            </div>
        </div>
    </div>
    
    <div id="menu" class="hidden fixed top-0 left-0 w-64 h-full bg-white shadow-2xl z-50 p-6">
        <button type="button" onclick="closeMenu()" class="absolute top-4 right-4 text-gray-500">
            <i class="fas fa-times text-xl"></i>
        </button>
        <div class="mt-8">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-teal text-white rounded-full flex items-center justify-center font-bold text-lg">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="ml-3">
                    <p class="font-bold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo ucfirst($user['user_type']); ?></p>
                </div>
            </div>
            <div class="space-y-4">
                <button type="button" onclick="openSettings()" class="w-full text-left flex items-center text-gray-700 hover:text-teal">
                    <i class="fas fa-cog w-6"></i>
                    <span class="ml-3">Settings</span>
                </button>
                <button type="button" onclick="openMyTasks()" class="w-full text-left flex items-center text-gray-700 hover:text-teal">
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
                             class="w-full p-4 border-2 border-gray-100 rounded-2xl mb-4 text-sm focus:border-teal focus:outline-none" rows="2" required></textarea>
                    
                    <div class="flex gap-3">
                        <input type="number" name="budget" placeholder="Budget ₹" 
                               class="flex-1 p-4 border-2 border-gray-100 rounded-2xl text-sm focus:border-teal focus:outline-none" required>
                        <button type="submit" class="bg-teal text-white px-8 py-4 rounded-2xl hover:bg-opacity-90 transition-colors font-medium">
                            <i class="fas fa-search"></i> Find
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="helpers-panel" class="hidden fixed inset-0 bg-black/50 z-40" onclick="closeHelpersPanel()">
            <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl max-h-96 overflow-y-auto" onclick="event.stopPropagation()">
                <div class="max-w-md mx-auto p-6">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                    <div id="helpers-list"></div>
                </div>
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

    <div id="my-tasks-panel" class="hidden fixed inset-0 bg-black/50 z-50" onclick="closeMyTasks()">
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
                        <select onchange="changeLanguage(this.value)" class="w-full p-3 border-2 border-gray-100 rounded-xl focus:border-teal focus:outline-none">
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
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl max-h-96 overflow-y-auto" onclick="event.stopPropagation()">
            <div class="max-w-md mx-auto p-6">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="font-bold mb-4 text-gray-900"><i class="fas fa-wallet text-teal mr-2"></i>Wallet</h3>
                
                <!-- Balance Display -->
                <div class="bg-gradient-to-r from-teal to-green-600 p-4 rounded-2xl mb-4 text-white text-center">
                    <div class="text-sm opacity-90 mb-1">Available Balance</div>
                    <div class="text-2xl font-bold">₹<?php echo number_format($user['wallet_balance'], 0); ?></div>
                </div>
                
                <!-- Withdraw Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Withdraw Money</span>
                        <span class="text-xs text-gray-500" id="withdraw-limit">2/3 left this week</span>
                    </div>
                    <button type="button" onclick="openWithdrawForm()" class="w-full bg-orange-500 text-white p-3 rounded-xl font-medium hover:bg-orange-600 transition-colors">
                        <i class="fas fa-money-bill-wave mr-2"></i>Withdraw Amount
                    </button>
                </div>
                
                <!-- Add Money Section -->
                <div>
                    <div class="text-sm font-medium text-gray-700 mb-3">Add Money</div>
                    
                    <!-- Quick Amount Buttons -->
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <button type="button" onclick="setAmount(10)" class="bg-gray-100 text-gray-700 p-3 rounded-xl font-medium hover:bg-teal hover:text-white transition-colors">
                            +₹10
                        </button>
                        <button type="button" onclick="setAmount(25)" class="bg-gray-100 text-gray-700 p-3 rounded-xl font-medium hover:bg-teal hover:text-white transition-colors">
                            +₹25
                        </button>
                        <button type="button" onclick="setAmount(50)" class="bg-gray-100 text-gray-700 p-3 rounded-xl font-medium hover:bg-teal hover:text-white transition-colors">
                            +₹50
                        </button>
                        <button type="button" onclick="setAmount(100)" class="bg-gray-100 text-gray-700 p-3 rounded-xl font-medium hover:bg-teal hover:text-white transition-colors">
                            +₹100
                        </button>
                        <button type="button" onclick="setAmount(500)" class="bg-gray-100 text-gray-700 p-3 rounded-xl font-medium hover:bg-teal hover:text-white transition-colors">
                            +₹500
                        </button>
                        <button type="button" onclick="showCustomAmount()" class="bg-gray-100 text-gray-700 p-3 rounded-xl font-medium hover:bg-teal hover:text-white transition-colors">
                            Other
                        </button>
                    </div>
                    
                    <!-- Custom Amount Input (Hidden by default) -->
                    <div id="custom-amount-section" class="hidden mb-4">
                        <input type="number" id="custom-amount" placeholder="Enter amount" min="10" 
                               class="w-full p-3 border-2 border-gray-100 rounded-xl focus:border-teal focus:outline-none">
                    </div>
                    
                    <!-- Selected Amount Display -->
                    <div id="selected-amount" class="hidden bg-teal/10 border border-teal/20 p-3 rounded-xl mb-4">
                        <div class="text-sm text-teal font-medium">Selected Amount: ₹<span id="amount-display">0</span></div>
                    </div>
                    
                    <!-- Add Money Button -->
                    <button type="button" onclick="processAddMoney()" id="add-money-btn" class="w-full bg-teal text-white p-4 rounded-xl font-medium opacity-50 cursor-not-allowed" disabled>
                        <i class="fas fa-plus mr-2"></i>Add to Wallet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="notif-panel" class="hidden fixed inset-0 bg-black/50 z-50" onclick="closeNotifications()">
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl max-h-96 overflow-y-auto" onclick="event.stopPropagation()">
            <div class="max-w-md mx-auto p-6">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="font-bold mb-4 text-gray-900"><i class="fas fa-bell text-gray-400 mr-2"></i>Notifications</h3>
                <div id="notif-list">
                    <div class="text-gray-400 text-center py-4">No new notifications</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Test if scripts are loading
        console.log('Dashboard loaded');
        
        // Inline critical functions to ensure they work
        function openMenu() {
            console.log('Opening menu');
            const menu = document.getElementById('menu');
            if (menu) {
                menu.classList.remove('hidden');
                console.log('Menu opened');
            } else {
                console.error('Menu element not found');
            }
        }
        
        function closeMenu() {
            console.log('Closing menu');
            const menu = document.getElementById('menu');
            if (menu) {
                menu.classList.add('hidden');
                console.log('Menu closed');
            }
        }
        
        function openWallet() {
            console.log('Opening wallet');
            const panel = document.getElementById('wallet-panel');
            if (panel) {
                panel.classList.remove('hidden');
                console.log('Wallet opened');
            }
        }
        
        function closeWallet() {
            console.log('Closing wallet');
            const panel = document.getElementById('wallet-panel');
            if (panel) {
                panel.classList.add('hidden');
                console.log('Wallet closed');
            }
        }
        
        function openNotifications() {
            console.log('Opening notifications');
            const panel = document.getElementById('notif-panel');
            if (panel) {
                panel.classList.remove('hidden');
                console.log('Notifications opened');
            }
        }
        
        function closeNotifications() {
            console.log('Closing notifications');
            const panel = document.getElementById('notif-panel');
            if (panel) {
                panel.classList.add('hidden');
                console.log('Notifications closed');
            }
        }
        
        function openSettings() {
            console.log('Opening settings');
            closeMenu();
            const panel = document.getElementById('settings-panel');
            if (panel) {
                panel.classList.remove('hidden');
                console.log('Settings opened');
            }
        }
        
        function closeSettings() {
            console.log('Closing settings');
            const panel = document.getElementById('settings-panel');
            if (panel) {
                panel.classList.add('hidden');
                console.log('Settings closed');
            }
        }
        
        function openMyTasks() {
            console.log('Opening my tasks');
            closeMenu();
            const panel = document.getElementById('my-tasks-panel');
            if (panel) {
                panel.classList.remove('hidden');
                console.log('My tasks opened');
            }
        }
        
        function closeMyTasks() {
            console.log('Closing my tasks');
            const panel = document.getElementById('my-tasks-panel');
            if (panel) {
                panel.classList.add('hidden');
                console.log('My tasks closed');
            }
        }
        
        function closeHelpersPanel() {
            console.log('Closing helpers panel');
            const panel = document.getElementById('helpers-panel');
            if (panel) {
                panel.classList.add('hidden');
                console.log('Helpers panel closed');
            }
        }
        
        function toggleMode(isHelperMode) {
            console.log('Toggling mode:', isHelperMode);
            const newMode = isHelperMode ? 'helper' : 'customer';
            fetch('api/set_mode.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `mode=${newMode}`
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    location.reload();
                } else {
                    alert('Failed to switch mode');
                    document.getElementById('mode-toggle').checked = !isHelperMode;
                }
            })
            .catch(e => {
                console.error('Mode toggle error:', e);
                alert('Error switching mode');
                document.getElementById('mode-toggle').checked = !isHelperMode;
            });
        }
        
        function startListening() {
            console.log('Starting voice recognition');
            if (!('webkitSpeechRecognition' in window)) {
                alert('Voice not supported in this browser');
                return;
            }
            
            const recognition = new webkitSpeechRecognition();
            recognition.lang = 'en-IN';
            recognition.continuous = false;
            recognition.interimResults = false;
            
            const statusText = document.getElementById('status-text');
            const micBtn = document.querySelector('[onclick="startListening()"]');
            
            recognition.onstart = () => {
                console.log('Voice recognition started');
                if (micBtn) micBtn.style.backgroundColor = '#ff6b35';
                if (statusText) statusText.textContent = 'Listening...';
            };
            
            recognition.onresult = (e) => {
                console.log('Voice result:', e.results[0][0].transcript);
                const taskInput = document.getElementById('task_input');
                if (taskInput) {
                    taskInput.value = e.results[0][0].transcript;
                }
                if (statusText) statusText.textContent = 'Got it!';
            };
            
            recognition.onerror = (e) => {
                console.error('Voice recognition error:', e);
                if (statusText) statusText.textContent = 'Error occurred';
                if (micBtn) micBtn.style.backgroundColor = '';
            };
            
            recognition.onend = () => {
                console.log('Voice recognition ended');
                if (micBtn) micBtn.style.backgroundColor = '';
                setTimeout(() => {
                    if (statusText) statusText.textContent = '';
                }, 2000);
            };
            
            recognition.start();
        }
        
        function postTask(event) {
            console.log('Posting task');
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('api/post_task.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                console.log('Task post result:', d);
                if (d.success) {
                    alert('Task posted successfully!');
                    event.target.reset();
                } else {
                    alert('Failed to post task: ' + (d.message || 'Unknown error'));
                }
            })
            .catch(e => {
                console.error('Task post error:', e);
                alert('Error posting task');
            });
        }
        
        let selectedAmount = 0;
        
        function setAmount(amount) {
            console.log('Setting amount:', amount);
            selectedAmount = amount;
            
            // Hide custom input
            document.getElementById('custom-amount-section').classList.add('hidden');
            
            // Show selected amount
            document.getElementById('amount-display').textContent = amount;
            document.getElementById('selected-amount').classList.remove('hidden');
            
            // Enable add money button
            const btn = document.getElementById('add-money-btn');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.classList.add('hover:bg-opacity-90');
        }
        
        function showCustomAmount() {
            console.log('Showing custom amount input');
            const section = document.getElementById('custom-amount-section');
            section.classList.remove('hidden');
            document.getElementById('custom-amount').focus();
            
            // Listen for input changes
            document.getElementById('custom-amount').oninput = function() {
                const amount = parseInt(this.value);
                if (amount >= 10) {
                    setAmount(amount);
                }
            };
        }
        
        function processAddMoney() {
            console.log('Processing add money, amount:', selectedAmount);
            
            if (!selectedAmount || selectedAmount < 10) {
                alert('Please select an amount of at least ₹10');
                return;
            }
            
            fetch('api/add_money.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `amount=${selectedAmount}`
            })
            .then(r => r.json())
            .then(d => {
                console.log('Add money result:', d);
                if (d.success) {
                    alert(`₹${selectedAmount} added to wallet!`);
                    setTimeout(() => {
                        closeWallet();
                        location.reload();
                    }, 1000);
                } else {
                    alert('Failed to add money: ' + (d.message || 'Unknown error'));
                }
            })
            .catch(e => {
                console.error('Add money error:', e);
                alert('Error adding money');
            });
        }
        
        function openWithdrawForm() {
            console.log('Opening withdraw form');
            const amount = prompt('Enter amount to withdraw (₹10 minimum):');
            if (!amount || amount < 10) {
                alert('Please enter amount of at least ₹10');
                return;
            }
            
            if (confirm(`Withdraw ₹${amount} from wallet?`)) {
                fetch('api/withdraw_money.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `amount=${amount}`
                })
                .then(r => r.json())
                .then(d => {
                    console.log('Withdraw result:', d);
                    if (d.success) {
                        alert(`₹${amount} withdrawal initiated!`);
                        setTimeout(() => {
                            closeWallet();
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Failed to withdraw: ' + (d.message || 'Unknown error'));
                    }
                })
                .catch(e => {
                    console.error('Withdraw error:', e);
                    alert('Error processing withdrawal');
                });
            }
        }
        
        function changeLanguage(lang) {
            console.log('Changing language to:', lang);
            localStorage.setItem('app_language', lang);
            alert('Language changed to ' + lang);
            closeSettings();
        }
        
        function checkLocationPermission() {
            console.log('Checking location permission');
            if (!navigator.geolocation) {
                alert('Geolocation not supported');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    console.log('Location granted:', position.coords);
                    const alertDiv = document.getElementById('location-alert');
                    if (alertDiv) alertDiv.classList.add('hidden');
                },
                (error) => {
                    console.error('Location denied:', error);
                    alert('Please enable location access for better experience');
                }
            );
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing app');
            
            // Set default language if not set
            if (!localStorage.getItem('app_language')) {
                localStorage.setItem('app_language', 'en-IN');
            }
            
            // Check location permission
            setTimeout(checkLocationPermission, 1000);
            
            console.log('App initialized successfully');
        });
    </script>
    
    <!-- Load external scripts after inline functions -->
    <script src="assets/js/app.js" onerror="console.error('Failed to load app.js')"></script>
    <script src="assets/js/map_logic.js" onerror="console.error('Failed to load map_logic.js')"></script>
</body>
</html>
