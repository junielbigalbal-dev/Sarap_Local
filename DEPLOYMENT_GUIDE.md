# 🚀 Sarap Local - Deployment Guide

Complete guide for deploying Sarap Local to production.

## ✅ Pre-Deployment Checklist

### System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- 500MB free disk space
- HTTPS certificate (for production)

### Required PHP Extensions
- mysqli
- json
- fileinfo
- gd (for image processing)
- curl

## 📋 Installation Steps

### 1. Database Setup

```bash
# Connect to MySQL
mysql -u root -p

# Create database
CREATE DATABASE sarap_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit MySQL
exit
```

Or use the automatic setup:

```bash
# Navigate to project directory
cd /path/to/sarap_local

# Run setup script (via browser)
http://localhost/sarap_local/setup.php
```

### 2. Create Upload Directories

```bash
# Create directories
mkdir -p uploads/products
mkdir -p uploads/profiles
mkdir -p uploads/logos
mkdir -p uploads/reels
mkdir -p logs

# Set permissions (Linux/Mac)
chmod 755 uploads/products
chmod 755 uploads/profiles
chmod 755 uploads/logos
chmod 755 uploads/reels
chmod 755 logs

# For Windows, ensure IUSR has write permissions
```

### 3. Configure Database Connection

Edit `db.php`:

```php
$db_config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'your_password',
    'db'   => 'sarap_local',
    'charset' => 'utf8mb4'
];
```

### 4. Environment Configuration

Create `.env` file (optional):

```
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=sarap_local
APP_ENV=production
APP_DEBUG=false
```

### 5. Security Configuration

#### Enable HTTPS

```apache
# In Apache vhost configuration
<VirtualHost *:443>
    ServerName saraplocal.com
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
</VirtualHost>
```

#### Set File Permissions

```bash
# Make config files read-only
chmod 644 db.php
chmod 644 includes/*.php

# Make logs directory writable
chmod 755 logs
```

#### Configure .htaccess

```apache
# Enable mod_rewrite
RewriteEngine On

# Redirect HTTP to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevent direct access to sensitive files
<FilesMatch "\.(env|sql|json|log)$">
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes
```

## 🔐 Security Hardening

### 1. Database Security

```sql
-- Create dedicated database user
CREATE USER 'sarap_user'@'localhost' IDENTIFIED BY 'strong_password';

-- Grant permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON sarap_local.* TO 'sarap_user'@'localhost';

-- Remove unnecessary privileges
REVOKE ALL PRIVILEGES ON *.* FROM 'sarap_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;
```

### 2. PHP Configuration

Edit `php.ini`:

```ini
; Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Set upload limits
upload_max_filesize = 100M
post_max_size = 100M

; Session security
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = Strict

; Error reporting
display_errors = Off
log_errors = On
error_log = /path/to/logs/php_errors.log
```

### 3. Apache Configuration

```apache
# Disable directory listing
<Directory /var/www/sarap_local>
    Options -Indexes
    AllowOverride All
    Require all granted
</Directory>

# Set security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

## 🗄️ Database Migrations

### Run Migrations

```bash
# Using MySQL command line
mysql -u sarap_user -p sarap_local < db/migrations/001_create_tables.sql

# Or via PHP
php db/migrations/run_migrations.php
```

### Create Backup

```bash
# Backup database
mysqldump -u sarap_user -p sarap_local > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
tar -czf sarap_local_backup_$(date +%Y%m%d_%H%M%S).tar.gz uploads/ logs/
```

## 📊 Performance Optimization

### 1. Enable Caching

```php
// In db.php
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

// Enable query caching
// SET GLOBAL query_cache_size = 268435456;
```

### 2. Database Optimization

```sql
-- Optimize tables
OPTIMIZE TABLE users;
OPTIMIZE TABLE products;
OPTIMIZE TABLE orders;
OPTIMIZE TABLE notifications;

-- Analyze tables
ANALYZE TABLE users;
ANALYZE TABLE products;
ANALYZE TABLE orders;
```

### 3. Enable Gzip Compression

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### 4. Browser Caching

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## 🧪 Testing

### 1. Functionality Testing

```bash
# Test login
curl -X POST http://localhost/sarap_local/login.php \
  -d "email=vendor1@saraplocal.com&password=test123"

# Test API endpoints
curl http://localhost/sarap_local/api/search-advanced.php?q=adobo

# Test file uploads
curl -F "image=@test.jpg" http://localhost/sarap_local/api/profile.php?action=upload_image
```

### 2. Security Testing

```bash
# Test SQL injection protection
curl "http://localhost/sarap_local/api/search-advanced.php?q='; DROP TABLE users; --"

# Test XSS protection
curl "http://localhost/sarap_local/api/search-advanced.php?q=<script>alert('xss')</script>"

# Test CSRF protection
curl -X POST http://localhost/sarap_local/api/cart.php?action=add \
  -d "product_id=1&quantity=1"
```

### 3. Performance Testing

```bash
# Load testing with Apache Bench
ab -n 1000 -c 10 http://localhost/sarap_local/

# Stress testing
wrk -t12 -c400 -d30s http://localhost/sarap_local/
```

## 📈 Monitoring

### 1. Log Monitoring

```bash
# Monitor PHP errors
tail -f logs/php_errors.log

# Monitor application logs
tail -f logs/app_*.log

# Monitor Apache errors
tail -f /var/log/apache2/error.log
```

### 2. Database Monitoring

```sql
-- Check slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Monitor connections
SHOW PROCESSLIST;

-- Check table sizes
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'sarap_local'
ORDER BY size_mb DESC;
```

### 3. System Monitoring

```bash
# Monitor disk usage
df -h

# Monitor memory usage
free -h

# Monitor CPU usage
top -b -n 1 | head -20
```

## 🚨 Troubleshooting

### Database Connection Issues

```php
// Check connection
if ($conn->connect_error) {
    error_log('Connection Error: ' . $conn->connect_error);
    die('Database connection failed');
}

// Check credentials
echo "Host: " . $db_config['host'] . "\n";
echo "User: " . $db_config['user'] . "\n";
echo "Database: " . $db_config['db'] . "\n";
```

### File Upload Issues

```bash
# Check directory permissions
ls -la uploads/

# Check PHP upload settings
php -i | grep upload

# Check disk space
df -h
```

### Performance Issues

```sql
-- Check for missing indexes
SELECT * FROM information_schema.statistics WHERE table_schema = 'sarap_local';

-- Check query performance
EXPLAIN SELECT * FROM products WHERE product_name LIKE '%adobo%';

-- Monitor slow queries
SELECT * FROM mysql.slow_log;
```

## 📞 Support

For issues or questions:
1. Check logs in `logs/` directory
2. Review error messages in browser console (F12)
3. Check database connection in `db.php`
4. Verify file permissions on upload directories
5. Review Apache error logs

## ✅ Post-Deployment

- [ ] Test all features
- [ ] Verify database backups
- [ ] Monitor error logs
- [ ] Set up automated backups
- [ ] Configure email notifications
- [ ] Update DNS records
- [ ] Enable SSL certificate
- [ ] Set up monitoring alerts
- [ ] Document custom configurations
- [ ] Train support team

---

**Made with ❤️ for local food lovers**

Sarap Local - Connecting communities through food.
