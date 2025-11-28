Sahay: Technical Specification & Implementation Guide

Version: 1.1 (Mode Switch Architecture Update) | Stack: PHP, MySQL, HTML, CSS, JS

1. System Architecture

Since you are deploying to Hostinger (Shared Hosting), we will use a classic LAMP Stack (Linux, Apache, MySQL, PHP). This is cost-effective and robust.

Frontend: HTML5, CSS3 (Tailwind CSS via CDN), Vanilla JavaScript.

Backend: PHP 8.0+ (Core PHP for performance, no heavy frameworks).

Database: MySQL / MariaDB.

Real-time Operations: AJAX Short-Polling (checks for updates every 5-10 seconds) to avoid server timeouts on shared hosting.

2. Free APIs & Tools Strategy

To keep costs at Zero, we replace paid Google APIs with open-source alternatives:

Feature

Paid Solution (Avoid)

Free Solution (Use This)

Implementation Note

Maps & Location

Google Maps API

OpenStreetMap + Leaflet.js

Completely free. Uses standard Lat/Long coordinates.

Voice-to-Text

Google Cloud Speech

Web Speech API

Native browser API. Works offline on Android. No API key needed.

Icons

FontAwesome Pro

FontAwesome Free (CDN)

Use standard CDN links.

Database

AWS RDS

Hostinger MySQL

Included in your hosting plan.

SMS/OTP

Twilio

PHP Mailer / Mock

For MVP, verify via Email or "Mock" OTP (Fixed 1234) to save money.

3. Folder Structure

The dashboards have been consolidated for an efficient single-app architecture.

sahay/
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   ├── js/
│   │   ├── app.js          # Main logic
│   │   ├── map_logic.js    # Leaflet Map handling
│   │   └── voice_logic.js  # Speech recognition
│   └── images/
├── config/
│   └── db_connect.php      # Database connection file
├── api/                    # JSON Endpoints for App interactions
│   ├── login.php
│   ├── post_task.php
│   ├── get_helpers.php     # The "Radar" logic
│   ├── update_location.php # Mandatory for Helper Mode
│   └── set_mode.php        # NEW: Handles user mode switch
├── uploads/                # For KYC images/Profile pics
├── index.php               # Landing Page / Login
├── dashboard.php           # CONSOLIDATED: Renders Customer or Helper UI
└── sql/
    └── sahay.sql           # Database import file


4. Database Schema (MySQL)

Note: The users table's user_type column defines the user's permanent role (Customer/Helper). The Active Mode will be controlled via a PHP Session variable ($_SESSION['active_mode']) to allow the toggle.

-- 1. Users Table (Stores both Customers and Helpers)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    user_type ENUM('customer', 'helper') NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Helper Profiles (Extra info for workers)
CREATE TABLE helper_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    skill_tags VARCHAR(255), -- e.g., "Electrician, Labor, Moving"
    base_rate DECIMAL(10, 2),
    current_lat DECIMAL(10, 8),
    current_lng DECIMAL(11, 8),
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 3. Tasks Table
CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    helper_id INT NULL,
    description TEXT,
    voice_note_url VARCHAR(255),
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    agreed_price DECIMAL(10, 2),
    pickup_lat DECIMAL(10, 8),
    pickup_lng DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id)
);


5. Core PHP Algorithms (The Brains)

A. Database Connection (config/db_connect.php)

<?php
$host = "localhost"; // Hostinger usually uses 'localhost'
$user = "u123456789_sahay_user"; // Your DB Username
$pass = "YourStrongPassword!";
$db   = "u123456789_sahay_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>


B. "The Radar" - Finding Nearby Helpers (api/get_helpers.php)

This uses the Haversine Formula in SQL to find helpers within 5KM.

<?php
include '../config/db_connect.php';

$user_lat = $_GET['lat'];
$user_lng = $_GET['lng'];
$radius_km = 5;

// SQL Magic: Calculate distance based on Lat/Lng
$sql = "SELECT user_id, skill_tags, base_rate, 
       ( 6371 * acos( cos( radians($user_lat) ) * cos( radians( current_lat ) ) 
       * cos( radians( current_lng ) - radians($user_lng) ) + sin( radians($user_lat) ) 
       * sin( radians( current_lat ) ) ) ) AS distance 
       FROM helper_profiles 
       HAVING distance < $radius_km 
       ORDER BY distance ASC 
       LIMIT 10";

$result = $conn->query($sql);

$helpers = [];
while($row = $result->fetch_assoc()) {
    $helpers[] = $row;
}

echo json_encode($helpers);
?>


C. Voice-to-Text Logic (assets/js/voice_logic.js)

This runs in the browser. No server needed.

function startListening() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Voice not supported in this browser. Please use Chrome.");
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'hi-IN'; // Sets language to Hindi (India)
    // You can dynamically change this to 'en-IN', 'bn-IN', etc.
    
    recognition.onstart = function() {
        document.getElementById('mic-icon').style.color = 'red';
        document.getElementById('status-text').innerText = "Listening...";
    };

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        // Put the text into the input box
        document.getElementById('task_input').value = transcript;
        
        // Auto-search or categorize based on keywords
        if(transcript.includes("bijli") || transcript.includes("electrician")) {
             filterHelpers('electrician');
        }
    };

    recognition.start();
}


D. Map Integration (assets/js/map_logic.js)

Using Leaflet.js (Free).

// Initialize map
var map = L.map('map').setView([20.5937, 78.9629], 5); // Center of India

// Add OpenStreetMap tiles (Free)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Function to add a helper marker
function addHelperMarker(lat, lng, name) {
    L.marker([lat, lng]).addTo(map)
        .bindPopup(`<b>${name}</b><br>Available Helper`)
        .openPopup();
}


E. NEW: Mode Switch Logic (api/set_mode.php)

This simple endpoint handles the AJAX call from the frontend toggle.

<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the requested new mode
$new_mode = $_POST['mode'] ?? 'customer'; // Default to customer mode

// Validate the requested mode
if ($new_mode === 'customer' || $new_mode === 'helper') {
    $_SESSION['active_mode'] = $new_mode;
    
    // Crucial Logic: When switching to customer mode, set helper profile availability to FALSE
    if ($new_mode === 'customer') {
        include '../config/db_connect.php';
        $user_id = $_SESSION['user_id'];
        
        // Ensure the helper is marked unavailable when they switch off the 'helper' mode
        $stmt = $conn->prepare("UPDATE helper_profiles SET is_available = FALSE WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'mode' => $new_mode]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid mode specified']);
}
?>


F. Dashboard Rendering Logic (dashboard.php Snippet)

This is the PHP logic in dashboard.php that determines which UI sections to display based on the active session mode.

<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$active_mode = $_SESSION['active_mode'] ?? 'customer'; // Default view is customer

// The full HTML for dashboard.php starts here...
?>

<div class="app-container">
    <!-- Mode Switch Toggle UI (Always Visible for eligible users) -->
    <?php if ($_SESSION['user_type'] === 'helper'): // Only show toggle if user is registered as a helper ?>
        <div class="mode-switcher">
            <label for="mode-toggle">Mode:</label>
            <input type="checkbox" id="mode-toggle" onchange="toggleMode(this.checked)" 
                <?php echo ($active_mode === 'helper') ? 'checked' : ''; ?>>
            <span class="mode-label"><?php echo ($active_mode === 'helper') ? 'Helping (Live)' : 'Seeking Help'; ?></span>
        </div>
    <?php endif; ?>

    <!-- RENDER CUSTOMER UI -->
    <?php if ($active_mode === 'customer'): ?>
        <div id="customer-view">
            <h1>What help do you need today?</h1>
            <!-- Voice Input & Map integration here -->
        </div>
    <?php endif; ?>

    <!-- RENDER HELPER UI -->
    <?php if ($active_mode === 'helper'): ?>
        <div id="helper-view">
            <h1>You are LIVE. Waiting for nearby tasks...</h1>
            <!-- Live tasks list and status updates here -->
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleMode(isHelperMode) {
        const newMode = isHelperMode ? 'helper' : 'customer';
        
        fetch('api/set_mode.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `mode=${newMode}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload or re-render the dashboard to apply new mode
                window.location.reload(); 
            } else {
                console.error("Mode switch failed:", data.message);
                alert("Could not switch mode. Please try again.");
                // Revert toggle state on failure
                document.getElementById('mode-toggle').checked = !isHelperMode; 
            }
        });
    }
</script>
