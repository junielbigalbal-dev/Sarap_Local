# 📁 Sarap Local - File Structure

Complete guide to the project file structure and organization.

## Directory Tree

```
sarap_local/
├── api/                              # API Endpoints
│   ├── admin-stats.php              # Admin statistics
│   ├── cart.php                     # Shopping cart operations
│   ├── chat_system.php              # Chat messaging
│   ├── check_new_orders.php         # Order notifications
│   ├── customer_reels.php           # Customer video feed
│   ├── get_notifications.php        # Get notifications
│   ├── get_product_details.php      # Product details
│   ├── mark_all_notifications_read.php
│   ├── mark_notification_read.php
│   ├── notifications.php            # Notification management
│   ├── orders.php                   # Order management API
│   ├── profile.php                  # Profile management API
│   ├── search-advanced.php          # Advanced search API
│   ├── search.php                   # Basic search
│   ├── session_handler.php          # Session handling
│   ├── submit_review.php            # Review submission
│   ├── vendor_management.php        # Vendor management
│   ├── vendor_notifications.php     # Vendor notifications
│   └── vendor_reels.php             # Vendor video management
│
├── css/                              # Stylesheets
│   └── style.css                    # Main stylesheet
│
├── db/                               # Database
│   └── migrations/
│       └── 001_create_tables.sql    # Database schema
│
├── includes/                         # PHP Includes
│   ├── api-response.php             # API response helpers
│   ├── auth.php                     # Authentication functions
│   ├── back-button.php              # Back button component
│   ├── cache-control.php            # Cache control headers
│   ├── error-handler.php            # Error handling
│   ├── session_validator.php        # Session validation
│   └── validators.php               # Input validators
│
├── js/                               # JavaScript
│   ├── cart.js                      # Cart functionality
│   ├── notifications.js             # Notifications
│   ├── reels.js                     # Video reels
│   ├── search.js                    # Search functionality
│   └── vendor.js                    # Vendor dashboard
│
├── logs/                             # Application Logs
│   ├── app_YYYY-MM-DD.log          # Daily app logs
│   └── php_errors.log              # PHP errors
│
├── uploads/                          # User Uploads
│   ├── products/                    # Product images
│   ├── profiles/                    # Profile pictures
│   ├── logos/                       # Business logos
│   └── reels/                       # Video files
│
├── admin/                            # Admin Panel
│   └── (admin files)
│
├── images/                           # Static Images
│   ├── S.png                        # Logo
│   └── (other images)
│
├── admin.php                         # Admin dashboard
├── app_config.php                    # App configuration
├── chat.php                          # Chat page
├── customer.php                      # Customer dashboard
├── db.php                            # Database connection
├── error.php                         # Error page
├── index.php                         # Landing page
├── login.php                         # Login page
├── logout.php                        # Logout handler
├── manifest.json                     # PWA manifest
├── messages.php                      # Messages page
├── product.php                       # Product detail page
├── profile.php                       # Profile page
├── reels.php                         # Video reels feed
├── search.php                        # Search page
├── service-worker.js                 # Service worker
├── setup.php                         # Setup script
├── signup.php                        # Signup page
├── suggestions.php                   # Suggestions API
├── vendor.php                        # Vendor dashboard
├── vendor_profile.php                # Public vendor profile
│
├── README.md                         # Main documentation
├── QUICK_START.md                    # Quick start guide
├── SETUP_GUIDE.md                    # Setup instructions
├── DEPLOYMENT_GUIDE.md               # Deployment guide
├── TESTING_GUIDE.md                  # Testing procedures
├── FILE_STRUCTURE.md                 # This file
├── IMPLEMENTATION_SUMMARY.md         # Features summary
├── VERIFICATION_CHECKLIST.md         # Testing checklist
├── LOGIN_SYSTEM_FIXED.md            # Login fixes
├── SYSTEM_STATUS.php                 # System status page
└── .htaccess                         # Apache configuration
```

## File Descriptions

### Core Files

| File | Purpose |
|------|---------|
| `db.php` | Database connection with error handling |
| `index.php` | Landing page with hero section |
| `login.php` | User login page |
| `signup.php` | User registration page |
| `logout.php` | Logout handler |
| `setup.php` | Database initialization script |

### Dashboard Files

| File | Purpose |
|------|---------|
| `customer.php` | Customer dashboard with product browsing |
| `vendor.php` | Vendor dashboard with order/product management |
| `admin.php` | Admin dashboard (if implemented) |
| `profile.php` | User profile management |

### Feature Files

| File | Purpose |
|------|---------|
| `chat.php` | Real-time chat interface |
| `messages.php` | Messages page |
| `reels.php` | Video reels feed (TikTok-style) |
| `product.php` | Product detail page |
| `search.php` | Advanced search page |
| `vendor_profile.php` | Public vendor profile view |
| `suggestions.php` | Product suggestions |

### API Endpoints

| File | Purpose |
|------|---------|
| `api/search-advanced.php` | Advanced search with filters |
| `api/cart.php` | Shopping cart operations |
| `api/orders.php` | Order management |
| `api/profile.php` | Profile management |
| `api/vendor_notifications.php` | Vendor notifications |
| `api/vendor_reels.php` | Vendor video management |
| `api/customer_reels.php` | Customer video feed |
| `api/notifications.php` | General notifications |
| `api/chat_system.php` | Chat messaging |

### Include Files

| File | Purpose |
|------|---------|
| `includes/auth.php` | Authentication functions |
| `includes/validators.php` | Input validation helpers |
| `includes/api-response.php` | Standardized API responses |
| `includes/error-handler.php` | Global error handling |
| `includes/session_validator.php` | Session validation |
| `includes/cache-control.php` | Cache headers |

### Database Files

| File | Purpose |
|------|---------|
| `db/migrations/001_create_tables.sql` | Database schema |

### Static Assets

| File | Purpose |
|------|---------|
| `css/style.css` | Main stylesheet |
| `js/*.js` | JavaScript modules |
| `images/S.png` | Logo |
| `manifest.json` | PWA configuration |
| `service-worker.js` | Service worker for offline |

### Configuration Files

| File | Purpose |
|------|---------|
| `.htaccess` | Apache rewrite rules |
| `app_config.php` | Application configuration |

## Directory Permissions

### Required Permissions

```bash
# Readable by all
chmod 644 *.php
chmod 644 *.html
chmod 644 css/*
chmod 644 js/*

# Writable by web server
chmod 755 uploads/
chmod 755 uploads/products/
chmod 755 uploads/profiles/
chmod 755 uploads/logos/
chmod 755 uploads/reels/
chmod 755 logs/

# Database files (read-only)
chmod 644 db.php
chmod 644 includes/auth.php
```

## File Size Guidelines

### Recommended Sizes

| File Type | Max Size |
|-----------|----------|
| Product Images | 5 MB |
| Profile Images | 5 MB |
| Business Logos | 5 MB |
| Video Reels | 100 MB |
| CSS Files | 500 KB |
| JavaScript Files | 500 KB |

## Naming Conventions

### PHP Files
- Use lowercase with underscores: `search_advanced.php`
- API files in `api/` directory
- Include files in `includes/` directory

### CSS Files
- Use lowercase: `style.css`
- Use BEM naming: `.button--primary`

### JavaScript Files
- Use lowercase: `cart.js`
- Use camelCase for functions: `addToCart()`

### Database Files
- Use YYYY_MM_DD_HHMMSS format for migrations
- Use descriptive names: `001_create_tables.sql`

### Upload Files
- Use unique identifiers: `uniqid_filename.ext`
- Organize by type: `uploads/products/`, `uploads/profiles/`

## Critical Files

These files should be protected and backed up regularly:

- `db.php` - Database credentials
- `includes/auth.php` - Authentication logic
- `db/migrations/` - Database schema
- `logs/` - Application logs

## Backup Strategy

### Daily Backups
```bash
# Backup database
mysqldump -u user -p sarap_local > backup_$(date +%Y%m%d).sql

# Backup uploads
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/

# Backup code
tar -czf code_backup_$(date +%Y%m%d).tar.gz --exclude=uploads --exclude=logs .
```

### Retention Policy
- Daily backups: Keep 7 days
- Weekly backups: Keep 4 weeks
- Monthly backups: Keep 12 months

## Version Control

### .gitignore
```
# Sensitive files
db.php
.env
logs/
uploads/

# System files
.DS_Store
Thumbs.db
*.swp
*.swo

# IDE files
.vscode/
.idea/
*.sublime-project
```

### Git Workflow
```bash
# Clone repository
git clone <repository-url>

# Create feature branch
git checkout -b feature/feature-name

# Commit changes
git commit -m "feat: add feature description"

# Push to remote
git push origin feature/feature-name

# Create pull request
# Review and merge
```

---

**Last Updated:** 2025-11-20
**Version:** 1.0.0
