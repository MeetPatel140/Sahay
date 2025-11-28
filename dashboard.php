<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$active_mode = $_SESSION['active_mode'] ?? 'customer';
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
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        
        <?php if ($_SESSION['user_type'] === 'helper'): ?>
        <div class="mb-4 bg-white p-4 rounded-lg shadow">
            <label class="flex items-center">
                <input type="checkbox" id="mode-toggle" onchange="toggleMode(this.checked)" 
                    <?php echo ($active_mode === 'helper') ? 'checked' : ''; ?>>
                <span class="ml-2 font-semibold">
                    <?php echo ($active_mode === 'helper') ? 'Helping (Live)' : 'Seeking Help'; ?>
                </span>
            </label>
        </div>
        <?php endif; ?>

        <?php if ($active_mode === 'customer'): ?>
        <div id="customer-view" class="bg-white p-6 rounded-lg shadow">
            <h1 class="text-2xl font-bold mb-4">What help do you need today?</h1>
            
            <div class="mb-4">
                <textarea id="task_input" placeholder="Describe your task..." 
                         class="w-full p-3 border rounded-lg h-24"></textarea>
                <button onclick="startListening()" class="mt-2 bg-red-500 text-white px-4 py-2 rounded">
                    🎤 Voice Input
                </button>
            </div>
            
            <div id="map" style="height: 300px;" class="mb-4 rounded-lg"></div>
            
            <div id="helpers-list" class="space-y-2">
                <h3 class="font-semibold">Available Helpers Nearby:</h3>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($active_mode === 'helper'): ?>
        <div id="helper-view" class="bg-white p-6 rounded-lg shadow">
            <h1 class="text-2xl font-bold mb-4">You are LIVE. Waiting for nearby tasks...</h1>
            
            <div id="tasks-list" class="space-y-2">
                <p class="text-gray-600">No tasks available right now.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/map_logic.js"></script>
    <script src="assets/js/voice_logic.js"></script>
</body>
</html>