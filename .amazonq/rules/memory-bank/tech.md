# Sahayak - Technology Stack

## Programming Languages

### Backend
- **PHP 8.0+**: Core language for server-side logic
  - No frameworks (Laravel/Symfony avoided for performance on shared hosting)
  - MySQLi extension for database operations
  - Session management for authentication and mode switching
  - Prepared statements for SQL injection prevention

### Frontend
- **HTML5**: Semantic markup, geolocation API
- **CSS3**: Custom styles with Tailwind utility classes
- **JavaScript (ES6+)**: Vanilla JS, no frameworks
  - Async/await for AJAX operations
  - Web Speech API for voice recognition
  - Geolocation API for location tracking

### Database
- **MySQL 5.7+ / MariaDB 10.3+**: Relational database
  - InnoDB engine for ACID compliance
  - Spatial functions (Haversine formula) for geolocation
  - Foreign key constraints for referential integrity

## Build System & Dependencies

### No Build Process Required
- **Zero compilation**: PHP interpreted at runtime
- **No package managers**: All dependencies loaded via CDN
- **Direct deployment**: FTP/SFTP upload to Hostinger

### CDN Dependencies
```html
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Leaflet.js (Maps) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- FontAwesome (Icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
```

### Native Browser APIs (No External Dependencies)
- **Web Speech API**: `webkitSpeechRecognition` for voice-to-text
- **Geolocation API**: `navigator.geolocation.getCurrentPosition()`
- **Fetch API**: Modern AJAX replacement for XMLHttpRequest

## Development Environment

### Local Setup
```bash
# Requirements
- XAMPP/WAMP/MAMP (Apache + MySQL + PHP)
- PHP 8.0 or higher
- MySQL 5.7 or higher

# Installation
1. Clone repository to htdocs/www directory
2. Import sql/sahayak.sql into MySQL
3. Update config/db_connect.php with local credentials
4. Access via http://localhost/sahayak/
```

### Database Configuration
```php
// config/db_connect.php
$host = "localhost";
$user = "root";              // Local: root, Production: u123456789_sahay_user
$pass = "";                  // Local: empty, Production: strong password
$db   = "sahayak_db";        // Database name
```

## Production Deployment

### Hostinger Shared Hosting
- **Control Panel**: hPanel (Hostinger custom)
- **PHP Version**: Select 8.0+ in hPanel settings
- **Database**: Create MySQL database via hPanel
- **File Manager**: Upload via FTP (FileZilla) or hPanel File Manager
- **Domain**: Point to public_html/sahayak/ directory

### Deployment Commands
```bash
# Via FTP
ftp your-domain.com
put -r sahayak/

# Via Git (if Hostinger supports)
git clone https://github.com/yourusername/sahayak.git
```

### Environment-Specific Settings
```php
// Production: Enable error logging, disable display
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('/path/to/error.log');

// Development: Show errors
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## API Integrations (All Free)

### OpenStreetMap + Leaflet.js
- **Purpose**: Interactive maps, helper markers
- **Cost**: Free, no API key required
- **Tile Server**: `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`
- **Documentation**: https://leafletjs.com/

### Web Speech API
- **Purpose**: Voice-to-text for task posting
- **Cost**: Free, native browser API
- **Browser Support**: Chrome, Edge (WebKit-based)
- **Languages**: hi-IN (Hindi), en-IN (English), bn-IN (Bengali)
- **Limitations**: Requires HTTPS in production

### Geolocation API
- **Purpose**: Customer/helper location tracking
- **Cost**: Free, native browser API
- **Accuracy**: 10-50 meters (GPS), 100-1000 meters (WiFi/Cell)
- **Permissions**: User must grant location access

## Database Schema Version

### Current Version: 1.1
```sql
-- Core Tables
users               -- Authentication, wallet, user type
helper_profiles     -- Skills, rates, geolocation, availability
tasks               -- Task lifecycle, pricing, status
messages            -- In-app communication (if implemented)
transactions        -- Wallet history (if implemented)
ratings             -- Helper reviews (if implemented)
```

### Key Indexes
```sql
-- Performance optimization
CREATE INDEX idx_helper_location ON helper_profiles(current_lat, current_lng);
CREATE INDEX idx_task_status ON tasks(status, created_at);
CREATE INDEX idx_customer_tasks ON tasks(customer_id, status);
CREATE INDEX idx_helper_tasks ON tasks(helper_id, status);
```

## Performance Considerations

### Shared Hosting Limitations
- **Max Execution Time**: 30-60 seconds (avoid long-running scripts)
- **Memory Limit**: 128-256 MB (optimize queries, avoid large datasets)
- **Concurrent Connections**: Limited (use connection pooling)
- **No WebSockets**: Use AJAX short-polling instead

### Optimization Strategies
- **SQL Optimization**: Haversine formula in single query (no PHP loops)
- **Caching**: PHP sessions for user data, avoid repeated DB queries
- **Lazy Loading**: Load helpers/tasks on-demand, not on page load
- **Image Optimization**: Compress uploads, use WebP format
- **CDN Usage**: Offload static assets (CSS/JS) to CDNs

## Security Measures

### Implemented
- **Password Hashing**: `password_hash()` with bcrypt
- **Prepared Statements**: All SQL queries use `bind_param()`
- **Session Security**: `session_regenerate_id()` on login
- **Input Validation**: Server-side validation for all user inputs
- **HTTPS**: Required for geolocation and voice APIs

### Recommended Additions
- **CSRF Tokens**: Add to all forms
- **Rate Limiting**: Prevent API abuse
- **File Upload Validation**: Check MIME types, file sizes
- **SQL Injection Testing**: Regular security audits
- **XSS Prevention**: Escape output with `htmlspecialchars()`

## Testing Strategy

### Manual Testing
- **Browser Testing**: Chrome, Firefox, Safari, Edge
- **Mobile Testing**: Android Chrome, iOS Safari
- **Geolocation Testing**: Use browser dev tools to mock locations
- **Voice Testing**: Test with Hindi, English, Bengali inputs

### No Automated Testing
- **Rationale**: MVP phase, shared hosting limitations
- **Future**: Add PHPUnit for unit tests, Selenium for E2E tests

## Version Control

### Git Workflow
```bash
# Branches
main        # Production-ready code
develop     # Integration branch
feature/*   # Feature development

# Deployment
git push origin main  # Triggers manual deployment to Hostinger
```

### Ignored Files (.gitignore)
```
config/db_connect.php  # Contains credentials
uploads/*              # User-generated content
.htaccess              # Server-specific config
cookies.txt            # Session data
```

## Monitoring & Logging

### Error Logging
```php
// Log to file
error_log("Error message", 3, "/path/to/error.log");

// Log to database (future enhancement)
INSERT INTO error_logs (message, file, line, timestamp) VALUES (?, ?, ?, NOW());
```

### Analytics (Future)
- **Google Analytics**: Track user behavior (free tier)
- **Custom Logging**: Task completion rates, average response times
- **Database Queries**: Log slow queries for optimization
