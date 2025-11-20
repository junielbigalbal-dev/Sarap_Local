# ⚡ Sarap Local - Quick Reference Guide

Fast lookup for common tasks and information.

## 🚀 Quick Start (5 Minutes)

```bash
# 1. Access setup script
http://localhost/sarap_local/setup.php

# 2. Create upload directories
mkdir -p uploads/{products,profiles,logos,reels}

# 3. Access application
http://localhost/sarap_local/

# 4. Login with test account
Email: vendor1@saraplocal.com
Password: test123
```

## 👤 Test Accounts

| Role | Username | Email | Password |
|------|----------|-------|----------|
| Vendor | vendor1 | vendor1@saraplocal.com | test123 |
| Vendor | vendor2 | vendor2@saraplocal.com | test123 |
| Customer | customer1 | customer1@saraplocal.com | test123 |
| Customer | customer2 | customer2@saraplocal.com | test123 |

## 📁 Important Files

| File | Purpose |
|------|---------|
| `db.php` | Database connection |
| `setup.php` | Database initialization |
| `includes/auth.php` | Authentication |
| `includes/validators.php` | Input validation |
| `api/search-advanced.php` | Search API |
| `api/cart.php` | Cart API |
| `api/orders.php` | Orders API |

## 🔧 Common Tasks

### Initialize Database
```bash
# Via browser
http://localhost/sarap_local/setup.php

# Via MySQL
mysql -u root sarap_local < db/migrations/001_create_tables.sql
```

### Create Upload Directories
```bash
mkdir -p uploads/{products,profiles,logos,reels}
chmod 755 uploads/*
```

### Check Database Connection
```php
// In any PHP file
require_once 'db.php';
echo $conn->connect_error ? 'Error' : 'Connected';
```

### View Error Logs
```bash
tail -f logs/app_*.log
tail -f logs/php_errors.log
```

### Backup Database
```bash
mysqldump -u root sarap_local > backup_$(date +%Y%m%d).sql
```

## 🔐 Security Checklist

- [ ] Database credentials configured
- [ ] Upload directories created
- [ ] File permissions set (755 for dirs, 644 for files)
- [ ] HTTPS enabled
- [ ] Error logging enabled
- [ ] Session timeout configured
- [ ] Rate limiting enabled
- [ ] Backups scheduled

## 📊 Database Tables

| Table | Purpose |
|-------|---------|
| users | User accounts |
| products | Product listings |
| cart | Shopping cart |
| orders | Order management |
| order_items | Order details |
| reviews | Ratings |
| notifications | System notifications |
| vendor_reels | Videos |
| favorites | Saved items |
| messages | Chat |
| activity_logs | Audit trail |

## 🔗 API Endpoints

### Search
```
GET /api/search-advanced.php?q=adobo&price_min=0&price_max=500
```

### Cart
```
POST /api/cart.php?action=add (product_id, quantity)
GET  /api/cart.php?action=get
```

### Orders
```
POST /api/orders.php?action=create (items, address, payment_method)
GET  /api/orders.php?action=get
```

### Profile
```
GET  /api/profile.php?action=get
POST /api/profile.php?action=update (phone, bio, address)
```

## 📱 Responsive Breakpoints

| Device | Width | Columns |
|--------|-------|---------|
| Mobile | 375-640px | 1 |
| Tablet | 641-1024px | 2 |
| Desktop | 1025px+ | 3-4 |

## 🛡️ Security Features

- ✅ Prepared statements (SQL injection prevention)
- ✅ HTML escaping (XSS prevention)
- ✅ CSRF tokens (CSRF prevention)
- ✅ Bcrypt hashing (Password security)
- ✅ Session regeneration (Session security)
- ✅ Rate limiting (Brute force prevention)
- ✅ Input validation (Data integrity)
- ✅ File upload validation (File security)

## 🐛 Troubleshooting

### Database Connection Error
```
Check:
1. MySQL is running
2. Credentials in db.php
3. Database exists
4. User has permissions
```

### Upload Fails
```
Check:
1. Directory exists (uploads/products/)
2. Directory is writable (chmod 755)
3. File size < 5MB (images) or 100MB (videos)
4. File type is allowed
```

### Session Issues
```
Check:
1. Cookies enabled
2. Session timeout (1 hour)
3. Browser cache cleared
4. Session files writable
```

### Slow Performance
```
Check:
1. Database indexes
2. Query optimization
3. Server resources
4. File sizes
5. Network speed
```

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| README.md | Main documentation |
| QUICK_START.md | 5-minute setup |
| DEPLOYMENT_GUIDE.md | Production deployment |
| TESTING_GUIDE.md | Testing procedures |
| FILE_STRUCTURE.md | File organization |
| IMPROVEMENTS_SUMMARY.md | All improvements |
| COMPLETION_REPORT.md | Final report |

## 🔄 Deployment Workflow

```
1. Setup Database
   └─ Run setup.php

2. Create Directories
   └─ mkdir uploads/*

3. Configure Database
   └─ Edit db.php

4. Test Features
   └─ Login and test

5. Enable HTTPS
   └─ Configure SSL

6. Deploy to Production
   └─ Copy files to server

7. Monitor
   └─ Check logs regularly
```

## 📞 Support Resources

### For Developers
- Code in `includes/` and `api/`
- Database schema in `db/migrations/`
- API docs in `API_DOCUMENTATION.md`

### For Administrators
- Setup guide: `DEPLOYMENT_GUIDE.md`
- Troubleshooting: `DEPLOYMENT_GUIDE.md`
- Monitoring: Check `logs/` directory

### For Users
- Quick start: `QUICK_START.md`
- Features: `README.md`
- Help: Contact support

## ⚙️ Configuration

### Database
```php
// db.php
$db_config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'db'   => 'sarap_local'
];
```

### Session
```php
// Session timeout: 1 hour
$timeout = 3600;

// Rate limiting: 5 attempts per 15 minutes
$max_attempts = 5;
$lockout_time = 900;
```

### File Upload
```php
// Image max size: 5MB
$max_image_size = 5242880;

// Video max size: 100MB
$max_video_size = 104857600;
```

## 🎯 Performance Tips

1. **Database**
   - Use indexes on frequently queried columns
   - Optimize queries with EXPLAIN
   - Archive old data regularly

2. **Frontend**
   - Lazy load images
   - Minimize CSS/JS
   - Enable browser caching

3. **Backend**
   - Use prepared statements
   - Implement pagination
   - Cache frequently accessed data

4. **Server**
   - Enable gzip compression
   - Set proper PHP limits
   - Monitor server resources

## 📊 Monitoring Commands

```bash
# Check disk usage
df -h

# Check memory usage
free -h

# Check CPU usage
top -b -n 1

# Check MySQL status
mysqladmin -u root status

# Check Apache status
systemctl status apache2
```

## 🔐 Security Commands

```bash
# Set file permissions
chmod 644 *.php
chmod 755 uploads/

# Check file permissions
ls -la

# Backup database
mysqldump -u root sarap_local > backup.sql

# Restore database
mysql -u root sarap_local < backup.sql
```

## 🚨 Emergency Procedures

### Database Corruption
```bash
# Repair tables
mysqlcheck -u root -p sarap_local --repair

# Optimize tables
mysqlcheck -u root -p sarap_local --optimize
```

### Disk Full
```bash
# Find large files
du -sh * | sort -rh | head -10

# Clean old logs
rm logs/app_*.log
```

### High Memory Usage
```bash
# Restart Apache
systemctl restart apache2

# Restart MySQL
systemctl restart mysql
```

## 📞 Contact & Support

- **Documentation:** See README.md
- **Issues:** Check logs/ directory
- **Help:** Review DEPLOYMENT_GUIDE.md
- **Emergency:** Contact system administrator

---

**Last Updated:** 2025-11-20  
**Version:** 2.0.0

**Quick Links:**
- [README.md](README.md) - Main documentation
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Deployment
- [TESTING_GUIDE.md](TESTING_GUIDE.md) - Testing
- [setup.php](setup.php) - Database setup
