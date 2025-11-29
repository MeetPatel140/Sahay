# Sahayak - Project Structure

## Directory Organization

```
sahayak/
├── api/                    # Backend JSON endpoints (18 files)
├── assets/                 # Frontend resources
│   ├── css/               # Stylesheets
│   ├── js/                # Client-side logic
│   └── images/            # Static images
├── config/                # Configuration files
├── sql/                   # Database schemas
├── uploads/               # User-generated content (KYC, profiles)
├── .amazonq/              # AI assistant rules and memory
├── index.php              # Landing/authentication page
└── dashboard.php          # Main application interface
```

## Core Components

### 1. Authentication Layer (index.php)
- Dual-form interface (login/register) with JavaScript toggle
- Role selection during registration (customer/helper)
- Conditional helper fields (skills, base rate) shown only for helper registration
- Tailwind CSS for responsive design
- Redirects to dashboard.php on successful authentication

### 2. API Layer (api/)
**Authentication & Session**
- `login.php` - Credential validation, session initialization
- `register.php` - User creation with role-specific profile setup
- `logout.php` - Session cleanup
- `set_mode.php` - Customer/helper mode switching with availability updates

**Task Management**
- `post_task.php` - Create new task with geolocation
- `get_tasks.php` - Fetch tasks for helper (nearby pending tasks)
- `my_tasks.php` - Fetch user's task history
- `accept_task.php` - Helper accepts task, updates status
- `complete_task.php` - Mark task completed, trigger payment
- `cancel_task.php` - Cancel task with refund logic

**Discovery & Search**
- `get_helpers.php` - "The Radar" - Haversine-based proximity search
- `search_helpers.php` - Filter helpers by skills/availability
- `update_location.php` - Helper geolocation updates (mandatory in helper mode)

**Communication & Transactions**
- `send_message.php` - In-app messaging between customer/helper
- `get_messages.php` - Fetch conversation thread
- `get_notifications.php` - Real-time notification polling
- `rate_helper.php` - Post-task rating system
- `give_tip.php` - Additional payment to helper
- `add_money.php` - Wallet top-up
- `get_wallet_history.php` - Transaction history

**Profile Management**
- `update_profile.php` - Edit user/helper profile details

### 3. Frontend Layer (assets/)

**JavaScript Modules**
- `app.js` - Main application logic, AJAX polling, UI state management
- `map_logic.js` - Leaflet.js integration, marker management, map interactions
- `voice_logic.js` - Web Speech API wrapper, multilingual recognition, keyword filtering

**Styling**
- `style.css` - Custom styles complementing Tailwind CSS
- Tailwind CSS loaded via CDN for rapid prototyping

### 4. Configuration Layer (config/)
- `db_connect.php` - MySQLi connection singleton
- Credentials for Hostinger shared hosting environment
- Connection error handling

### 5. Data Layer (sql/)
- `sahayak.sql` - Complete database schema
- `sahay.sql` - Alternative/backup schema

## Architectural Patterns

### Session-Based Mode Switching
- `$_SESSION['user_type']` - Permanent role (customer/helper) set at registration
- `$_SESSION['active_mode']` - Dynamic mode (customer/helper) toggled by user
- Dashboard renders different UI based on `active_mode`
- Mode switch triggers availability update in `helper_profiles` table

### Geospatial Architecture
- Helpers store `current_lat`, `current_lng` in `helper_profiles` table
- Tasks store `pickup_lat`, `pickup_lng` for customer location
- Haversine formula in SQL calculates distance in kilometers
- Results filtered by `HAVING distance < radius_km` and sorted by proximity

### Real-Time Simulation
- AJAX short-polling (5-10 second intervals) instead of WebSockets
- Avoids shared hosting limitations (no persistent connections)
- Endpoints return JSON for incremental UI updates
- Polling targets: `get_tasks.php`, `get_notifications.php`, `get_messages.php`

### Single-Page Dashboard Pattern
- `dashboard.php` serves both customer and helper interfaces
- PHP conditional rendering based on `$_SESSION['active_mode']`
- JavaScript handles view transitions without page reload
- Mode toggle triggers AJAX call to `set_mode.php` then reloads page

## Data Flow Examples

### Task Posting Flow
1. Customer speaks into microphone → `voice_logic.js` captures transcript
2. Transcript analyzed for keywords (e.g., "bijli" → electrician)
3. `app.js` sends POST to `post_task.php` with description, location, price
4. Task inserted into `tasks` table with status='pending'
5. Nearby helpers' polling requests to `get_tasks.php` return new task
6. Helper sees task in their dashboard, can accept via `accept_task.php`

### Helper Discovery Flow
1. Customer opens dashboard → `app.js` gets geolocation from browser
2. AJAX GET to `get_helpers.php?lat=X&lng=Y`
3. SQL Haversine query finds helpers within 5km radius
4. Results returned as JSON array with distance, skills, rates
5. `map_logic.js` plots markers on Leaflet map
6. Customer clicks marker to view helper profile and initiate task

### Mode Switch Flow
1. Helper toggles switch in dashboard → `toggleMode(true)` called
2. JavaScript POSTs to `set_mode.php` with `mode=helper`
3. PHP updates `$_SESSION['active_mode'] = 'helper'`
4. If switching to customer mode, SQL updates `is_available = FALSE`
5. Page reloads, dashboard renders helper UI (live task feed)
6. `update_location.php` starts receiving periodic location updates

## Component Relationships

```
index.php (Auth) → api/login.php → dashboard.php (Main App)
                                         ↓
                    ┌────────────────────┴────────────────────┐
                    ↓                                         ↓
            Customer Mode                              Helper Mode
                    ↓                                         ↓
        ┌───────────┴───────────┐              ┌─────────────┴─────────────┐
        ↓                       ↓              ↓                           ↓
  voice_logic.js          map_logic.js   update_location.js          get_tasks.php
        ↓                       ↓              ↓                           ↓
  post_task.php          get_helpers.php  (Geolocation)            accept_task.php
        ↓                       ↓                                          ↓
    tasks table           helper_profiles                          complete_task.php
```

## Deployment Architecture
- **Hosting**: Hostinger shared hosting (LAMP stack)
- **Web Server**: Apache with mod_rewrite (.htaccess)
- **PHP Version**: 8.0+
- **Database**: MySQL/MariaDB (included in hosting plan)
- **CDN Resources**: Tailwind CSS, Leaflet.js, FontAwesome (free tiers)
- **File Uploads**: Local storage in `uploads/` directory
