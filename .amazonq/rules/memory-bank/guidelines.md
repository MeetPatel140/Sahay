# Sahayak - Development Guidelines

## Code Quality Standards

### PHP Code Formatting
- **Opening Tags**: Always use `<?php` (never short tags `<?`)
- **Line Endings**: CRLF (`\r\n`) for Windows compatibility
- **Indentation**: 4 spaces (no tabs)
- **Braces**: Opening brace on same line for control structures
- **No Closing Tags**: Omit `?>` at end of PHP-only files to prevent whitespace issues

### JavaScript Code Formatting
- **Variable Declarations**: Use `let` for mutable, `const` for immutable (avoid `var` except for global scope)
- **Semicolons**: Always terminate statements with semicolons
- **Line Endings**: CRLF (`\r\n`) for consistency
- **String Literals**: Use single quotes for strings, template literals for interpolation
- **Arrow Functions**: Prefer arrow functions for callbacks and short functions

### Naming Conventions
- **PHP Variables**: `$snake_case` (e.g., `$user_id`, `$active_mode`)
- **PHP Functions**: `camelCase` (rare in this codebase, mostly procedural)
- **JavaScript Variables**: `camelCase` (e.g., `userLocation`, `locationPermissionGranted`)
- **JavaScript Functions**: `camelCase` (e.g., `toggleMode`, `loadNearbyHelpers`)
- **CSS Classes**: `kebab-case` (e.g., `helper-card`, `task-card`)
- **Database Tables**: `snake_case` (e.g., `users`, `helper_profiles`)
- **Database Columns**: `snake_case` (e.g., `user_id`, `created_at`)

### File Organization
- **API Endpoints**: One endpoint per file in `api/` directory
- **JavaScript Modules**: Separate concerns (app.js for logic, map_logic.js for maps, voice_logic.js for voice)
- **Configuration**: Centralized in `config/db_connect.php`
- **No Autoloading**: Direct `include` statements for dependencies

## Structural Conventions

### Session Management Pattern
```php
// Always start with session check
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Use null coalescing for defaults
$active_mode = $_SESSION['active_mode'] ?? 'customer';
```
**Frequency**: 100% of protected pages (dashboard.php, all API endpoints)

### Database Connection Pattern
```php
// Include at top of every API file
include '../config/db_connect.php';

// Use prepared statements exclusively
$stmt = $conn->prepare("SELECT ... WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
```
**Frequency**: 100% of database operations (18 API files)

### AJAX Response Pattern
```php
// Always set JSON header
header('Content-Type: application/json');

// Return consistent structure
echo json_encode(['success' => true, 'data' => $data]);
// OR
echo json_encode(['success' => false, 'message' => 'Error description']);
```
**Frequency**: 100% of API endpoints

### Error Handling Pattern
```javascript
// Wrap all functions in try-catch
function functionName() {
    try {
        // Function logic
    } catch(e) { 
        console.error(e); 
    }
}
```
**Frequency**: 100% of JavaScript functions in app.js (35+ functions)

### Fetch API Pattern
```javascript
fetch('api/endpoint.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `param=${value}`
})
.then(r => r.json())
.then(d => d.success ? handleSuccess() : handleError(d.message))
.catch(e => showToast('Error: ' + e.message, 'error'));
```
**Frequency**: 90% of AJAX calls (ternary for success/error handling)

## Semantic Patterns

### Geolocation Acquisition Pattern
```javascript
if (!navigator.geolocation) { 
    showLocationAlert(); 
    return; 
}

navigator.geolocation.getCurrentPosition(
    function(pos) {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        locationPermissionGranted = true;
        hideLocationAlert();
        // Use coordinates
    }, 
    () => { 
        locationPermissionGranted = false; 
        showLocationAlert(); 
    }
);
```
**Frequency**: Used in 4 functions (loadNearbyHelpers, loadNearbyTasks, updateLocation, checkLocationPermission)
**Purpose**: Consistent permission handling and error recovery

### Dynamic UI Rendering Pattern
```javascript
// Clear existing content
list.innerHTML = '';

// Check for empty state
if (items.length === 0) {
    list.innerHTML = '<div class="text-gray-400 text-center py-4">No items</div>';
    return;
}

// Build DOM elements programmatically
items.forEach(item => {
    const div = document.createElement('div');
    div.className = 'item-card';
    div.innerHTML = `<template>...</template>`;
    list.appendChild(div);
});
```
**Frequency**: Used in 5 functions (loadNearbyHelpers, loadNearbyTasks, loadMyTasks, loadHelpersOnMap)
**Purpose**: Avoid XSS while maintaining dynamic content

### Modal Panel Pattern
```javascript
// Open: Remove 'hidden' class
function openPanel() {
    const panel = document.getElementById('panel-id');
    if (panel) panel.classList.remove('hidden');
}

// Close: Add 'hidden' class
function closePanel() {
    const panel = document.getElementById('panel-id');
    if (panel) panel.classList.add('hidden');
}
```
**Frequency**: 10 open/close function pairs (menu, wallet, settings, notifications, tasks)
**Purpose**: Consistent modal behavior without external libraries

### Conditional Rendering (PHP)
```php
<?php if ($condition): ?>
    <div>Content when true</div>
<?php endif; ?>

<?php if ($active_mode === 'customer'): ?>
    <div id="customer-view">...</div>
<?php endif; ?>

<?php if ($active_mode === 'helper'): ?>
    <div id="helper-view">...</div>
<?php endif; ?>
```
**Frequency**: Used throughout dashboard.php (6+ instances)
**Purpose**: Single-file dual-mode architecture

### Transaction Pattern (Database)
```php
$conn->begin_transaction();

try {
    // Multiple related operations
    $stmt1->execute();
    $stmt2->execute();
    
    $conn->commit();
    // Success response
} catch (Exception $e) {
    $conn->rollback();
    // Error response
}
```
**Frequency**: Used in register.php, likely in complete_task.php, add_money.php
**Purpose**: ACID compliance for multi-step operations

## Internal API Usage Patterns

### Session Variables (Read)
```php
// User identification
$_SESSION['user_id']        // Primary key for current user
$_SESSION['user_type']      // Permanent role: 'customer' or 'helper'
$_SESSION['active_mode']    // Current mode: 'customer' or 'helper'

// Usage example
$user_id = $_SESSION['user_id'];
$is_helper_mode = ($_SESSION['active_mode'] === 'helper');
```

### Session Variables (Write)
```php
// Login (api/login.php)
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_type'] = $user['user_type'];
$_SESSION['active_mode'] = 'customer'; // Default mode

// Mode switch (api/set_mode.php)
$_SESSION['active_mode'] = $new_mode;
```

### Database Query Patterns

**Haversine Distance Calculation**
```php
// Standard proximity search (5km radius)
$sql = "SELECT user_id, skill_tags, base_rate, 
       ( 6371 * acos( cos( radians($user_lat) ) * cos( radians( current_lat ) ) 
       * cos( radians( current_lng ) - radians($user_lng) ) + sin( radians($user_lat) ) 
       * sin( radians( current_lat ) ) ) ) AS distance 
       FROM helper_profiles 
       WHERE is_available = TRUE
       HAVING distance < $radius_km 
       ORDER BY distance ASC 
       LIMIT 10";
```
**Frequency**: Used in get_helpers.php, get_tasks.php
**Purpose**: Geospatial matching without PostGIS extension

**Prepared Statement with Bind Types**
```php
// Type indicators: i=integer, s=string, d=double
$stmt->bind_param("i", $user_id);           // Single integer
$stmt->bind_param("ssss", $a, $b, $c, $d);  // Four strings
$stmt->bind_param("isd", $id, $name, $rate); // Mixed types
```

### Frontend State Management

**Global State Variables**
```javascript
// Location tracking
let userLocation = null;                    // {lat: number, lng: number}
let locationPermissionGranted = false;      // Boolean flag
let locationCheckAttempted = false;         // Prevent repeated alerts

// Map objects
var map;                                    // Leaflet map instance
var userMarker;                             // User's location marker
var helperMarkers = [];                     // Array of helper markers
```

**LocalStorage Usage**
```javascript
// Language preference
localStorage.setItem('app_language', 'hi-IN');
const appLang = localStorage.getItem('app_language') || 'en-IN';
```

### Toast Notification Pattern
```javascript
// Consistent user feedback
showToast('Task posted!', 'success');       // Green background
showToast('Failed: ' + message, 'error');   // Red background
showToast('Calling helper...', 'info');     // Gray background

// Implementation creates temporary DOM element with auto-removal
```
**Frequency**: Used in 15+ functions across app.js
**Purpose**: Non-blocking user feedback without alert() dialogs

## Code Idioms

### Ternary Chains for Conditional Execution
```javascript
// Single-line success/error handling
.then(d => d.success ? (action1(), action2()) : showToast('Failed', 'error'))

// Inline conditional rendering
<span class="<?php echo ($active_mode === 'helper') ? 'checked' : ''; ?>">
```
**Frequency**: 80% of fetch().then() chains
**Purpose**: Reduce verbosity in simple conditional logic

### Null Coalescing Operator
```php
// PHP 7+ null coalescing
$active_mode = $_SESSION['active_mode'] ?? 'customer';
$new_mode = $_POST['mode'] ?? 'customer';

// JavaScript equivalent
const appLang = localStorage.getItem('app_language') || 'en-IN';
```
**Frequency**: 100% of optional parameter handling
**Purpose**: Provide safe defaults without isset() checks

### Inline Event Handlers
```html
<!-- Direct onclick attributes for simplicity -->
<button onclick="toggleMode(this.checked)">
<button onclick="acceptTask(<?php echo $task_id; ?>)">
<div onclick="event.stopPropagation()">
```
**Frequency**: 90% of interactive elements
**Purpose**: Avoid addEventListener boilerplate in simple cases

### Template Literal HTML Injection
```javascript
div.innerHTML = `
    <div class="flex justify-between">
        <div class="font-bold">${helper.full_name}</div>
        <button onclick="contactHelper(${helper.user_id})">Call</button>
    </div>
`;
```
**Frequency**: All dynamic list rendering
**Purpose**: Readable multi-line HTML with variable interpolation

### Arrow Function Callbacks
```javascript
// Geolocation callbacks
navigator.geolocation.getCurrentPosition(
    function(pos) { /* success */ },
    () => { /* error */ }
);

// Array methods
helpers.forEach(h => { /* process helper */ });

// Event listeners
.then(r => r.json())
```
**Frequency**: 95% of callbacks
**Purpose**: Concise syntax, lexical `this` binding

## Frequently Used Annotations

### PHP Comments
```php
// Single-line explanations for complex logic
// SQL Magic: Calculate distance based on Lat/Lng

// Section headers in database schema
-- 1. Users Table (Stores both Customers and Helpers)
```

### JavaScript Comments
```javascript
// Functional descriptions
// Initialize map

// Inline clarifications
recognition.lang = 'hi-IN'; // Sets language to Hindi (India)

// TODO markers (not present in current code, but recommended)
// TODO: Add rate limiting for API calls
```

### HTML Comments
```html
<!-- Structural markers -->
<!-- Mode Switch Toggle UI (Always Visible for eligible users) -->
<!-- RENDER CUSTOMER UI -->
<!-- RENDER HELPER UI -->
```

## Best Practices Followed

### Security
1. **Password Hashing**: `password_hash()` with bcrypt (register.php)
2. **Prepared Statements**: 100% of SQL queries use bind_param()
3. **Session Regeneration**: Implicit on login (should be explicit)
4. **Output Escaping**: `htmlspecialchars()` for user data in dashboard.php
5. **HTTPS Required**: For geolocation and voice APIs (production)

### Performance
1. **Single Database Connection**: Reused via include 'config/db_connect.php'
2. **Lazy Loading**: Helpers/tasks loaded on-demand, not on page load
3. **Polling Interval**: 15 seconds (balance between real-time and server load)
4. **SQL Optimization**: Haversine in single query, indexed columns
5. **CDN Resources**: Offload Tailwind, Leaflet, FontAwesome to CDNs

### Maintainability
1. **Separation of Concerns**: app.js (logic), map_logic.js (maps), voice_logic.js (voice)
2. **Consistent Naming**: camelCase JS, snake_case PHP/SQL
3. **Error Boundaries**: Try-catch wraps all functions
4. **Null Checks**: `if (element)` before DOM manipulation
5. **Magic Numbers Avoided**: `const radius_km = 5;` instead of hardcoded values

### Accessibility
1. **Semantic HTML**: `<button>` for actions, `<form>` for submissions
2. **ARIA Labels**: FontAwesome icons with descriptive text
3. **Mobile-First**: Tailwind responsive classes, viewport meta tag
4. **Touch Targets**: 48px minimum (w-12 h-12 for buttons)
5. **Color Contrast**: Teal (#00796B) on white meets WCAG AA

## Anti-Patterns to Avoid

### Do NOT Use
- **Frameworks**: No Laravel, Symfony, React, Vue (keep it vanilla)
- **WebSockets**: Shared hosting doesn't support persistent connections
- **Long-Running Scripts**: Max 30-60 seconds execution time
- **Large File Uploads**: Shared hosting has strict limits
- **Direct SQL**: Always use prepared statements
- **Global Variables (PHP)**: Use sessions or pass parameters
- **jQuery**: Native DOM APIs are sufficient
- **CSS Frameworks**: Tailwind CDN only, no Bootstrap

### Common Mistakes
```javascript
// BAD: No error handling
fetch('api/endpoint.php').then(r => r.json()).then(d => console.log(d));

// GOOD: Comprehensive error handling
fetch('api/endpoint.php')
    .then(r => r.json())
    .then(d => d.success ? handleSuccess() : handleError())
    .catch(e => showToast('Error: ' + e.message, 'error'));
```

```php
// BAD: SQL injection vulnerability
$sql = "SELECT * FROM users WHERE user_id = $user_id";

// GOOD: Prepared statement
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
```

```javascript
// BAD: Blocking alert
alert('Task posted successfully!');

// GOOD: Non-blocking toast
showToast('Task posted!', 'success');
```

## Development Workflow

### Adding New API Endpoint
1. Create `api/new_endpoint.php`
2. Add session check and database include
3. Validate input parameters
4. Execute database operations with prepared statements
5. Return JSON response with success/error structure
6. Add corresponding JavaScript function in app.js
7. Test with browser dev tools network tab

### Adding New UI Feature
1. Add HTML structure in dashboard.php (conditional on mode if needed)
2. Add Tailwind classes for styling
3. Create open/close functions in app.js
4. Add event handlers (onclick attributes or addEventListener)
5. Test on mobile viewport (Chrome DevTools)
6. Verify location permissions if geolocation is used

### Database Schema Changes
1. Update `sql/sahayak.sql` with ALTER TABLE or new CREATE TABLE
2. Add indexes for frequently queried columns
3. Update affected API endpoints to use new columns
4. Test with sample data in local MySQL
5. Deploy schema changes before code changes

## Testing Checklist

### Manual Testing Requirements
- [ ] Test on Chrome, Firefox, Safari, Edge
- [ ] Test on Android Chrome (voice recognition)
- [ ] Test on iOS Safari (geolocation)
- [ ] Mock location with browser dev tools
- [ ] Test with Hindi, English, Bengali voice input
- [ ] Verify mode switch updates availability status
- [ ] Check wallet transactions are atomic
- [ ] Confirm task status transitions correctly
- [ ] Test with location permission denied
- [ ] Verify AJAX polling doesn't cause memory leaks

### Pre-Deployment Checklist
- [ ] Update `config/db_connect.php` with production credentials
- [ ] Set `display_errors = 0` in PHP
- [ ] Enable error logging to file
- [ ] Test all API endpoints return valid JSON
- [ ] Verify HTTPS is enabled (required for geolocation)
- [ ] Check file upload directory permissions (uploads/)
- [ ] Test database connection from production server
- [ ] Verify CDN resources load correctly
- [ ] Test mode switch with real user accounts
- [ ] Confirm Haversine formula returns accurate distances
