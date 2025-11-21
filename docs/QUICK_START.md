# Sarap Local - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Database Migration
```bash
cd c:\xampp\htdocs\sarap_local
mysql -u root sarap_local < db/migrations/add_vendor_reels_table.sql
```

### Step 2: Create Upload Directory
```bash
mkdir -p uploads\reels
```

### Step 3: Start XAMPP
- Open XAMPP Control Panel
- Start Apache
- Start MySQL

### Step 4: Access Application
- Open browser: `http://localhost/sarap_local/`
- Click "Login" or "Sign Up"

### Step 5: Test Accounts
Use these credentials to test:

**Vendor Account:**
- Username: `vendor1`
- Password: `test123`
- Role: Vendor

**Customer Account:**
- Username: `customer1`
- Password: `test123`
- Role: Customer

---

## 📋 What's New

### For Vendors:
1. **Notifications** - Bell icon shows real-time notifications
2. **Food Reels** - Upload MP4/MOV videos (max 100MB)
3. **Map** - View Biliran Province map with your location
4. **Profile** - Updated with clean, formal layout

### For Customers:
1. **Reels Feed** - Browse food videos like TikTok
2. **Order Now** - Click overlay button to order from reels
3. **Auto-Play** - Videos auto-play when scrolled into view
4. **Profile** - Updated with clean, formal layout

---

## 🔑 Key Features

### ✅ Vendor Dashboard
- Real-time notifications
- Food reels upload & management
- Biliran Province map
- Product management
- Order management
- Inventory tracking

### ✅ Customer Dashboard
- Food reels feed (TikTok-style)
- Smart search
- Vendor browsing
- Cart management
- Favorites
- Order history

### ✅ Security
- Role-based access control
- Session validation
- Cross-role prevention
- Password hashing
- SQL injection prevention

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `vendor.php` | Vendor dashboard |
| `customer.php` | Customer dashboard |
| `reels.php` | Customer reels feed |
| `profile.php` | User profile settings |
| `login.php` | Login page |
| `api/vendor_notifications.php` | Notification API |
| `api/vendor_reels.php` | Vendor reel API |
| `api/customer_reels.php` | Customer reel API |

---

## 🧪 Quick Test

### Test Vendor Reels Upload
1. Login as vendor
2. Scroll to "Food Reels" section
3. Click "Upload Reel"
4. Select a video file (MP4)
5. Fill in title and description
6. Click "Upload Reel"
7. ✅ Reel appears in list

### Test Customer Reels Feed
1. Login as customer
2. Click film icon in header
3. ✅ Reels feed loads
4. Scroll to see more reels
5. Click "Order Now"
6. ✅ Product page opens

### Test Notifications
1. Login as vendor
2. Click bell icon
3. ✅ Notification dropdown opens
4. ✅ Shows notifications or "No notifications"

---

## 🐛 Troubleshooting

### Reels upload fails
```
✓ Check uploads/reels/ directory exists
✓ Check directory permissions (755)
✓ Check file size < 100MB
✓ Check file type is MP4/MOV
```

### Can't login
```
✓ Check MySQL is running
✓ Check db.php has correct credentials
✓ Check users table has test accounts
✓ Clear browser cookies
```

### Map not showing
```
✓ Check Google Maps API key in customer.php
✓ Check browser console for errors
✓ Try Leaflet fallback (should work)
```

### Notifications not showing
```
✓ Check notifications table has data
✓ Check browser console for errors
✓ Verify session is valid
```

---

## 📚 Documentation

- **SETUP_GUIDE.md** - Complete setup instructions
- **IMPLEMENTATION_SUMMARY.md** - All features implemented
- **VERIFICATION_CHECKLIST.md** - Testing checklist
- **QUICK_START.md** - This file

---

## 🎯 Next Steps

1. ✅ Run database migration
2. ✅ Create upload directory
3. ✅ Start XAMPP
4. ✅ Login with test accounts
5. ✅ Test all features
6. ✅ Read VERIFICATION_CHECKLIST.md
7. ✅ Deploy to production

---

## 💡 Tips

- **Vendor**: Upload reels to increase visibility
- **Customer**: Browse reels for food inspiration
- **Both**: Keep profile updated with accurate info
- **Admin**: Monitor notifications and orders

---

## 📞 Support

For issues:
1. Check browser console (F12)
2. Check server error logs
3. Read SETUP_GUIDE.md
4. Check VERIFICATION_CHECKLIST.md

---

## 🎉 You're All Set!

The application is fully functional and ready to use. Enjoy Sarap Local!

**Questions?** Check the documentation files or review the code comments.
