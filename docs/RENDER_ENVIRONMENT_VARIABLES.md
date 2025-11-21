# Render Environment Variables Setup
## Configure Your Database and App Settings

---

## 🎯 What Are Environment Variables?

Environment variables are settings that your app reads when it runs on Render.

They store:
- Database credentials
- API keys
- Passwords
- Configuration values

---

## ✅ Environment Variables You Need

For your Sarap Local app, add these:

### 1. Database Host
```
Name: DB_HOST
Value: db4free.net
```

### 2. Database Name
```
Name: DB_NAME
Value: sarap_local
```

### 3. Database User
```
Name: DB_USER
Value: sarap_user
```

### 4. Database Password
```
Name: DB_PASSWORD
Value: (your db4free password)
```

### 5. App Environment
```
Name: APP_ENV
Value: production
```

---

## 🔧 How to Add Environment Variables on Render

### Step 1: On Render Dashboard
- You should see: **"Environment Variables"** section
- Click: **"Add Environment Variable"**

### Step 2: Add First Variable (DB_HOST)
```
Name: DB_HOST
Value: db4free.net
```
Click: **"Add"**

### Step 3: Add Second Variable (DB_NAME)
```
Name: DB_NAME
Value: sarap_local
```
Click: **"Add"**

### Step 4: Add Third Variable (DB_USER)
```
Name: DB_USER
Value: sarap_user
```
Click: **"Add"**

### Step 5: Add Fourth Variable (DB_PASSWORD)
```
Name: DB_PASSWORD
Value: (your password from db4free)
```
Click: **"Add"**

### Step 6: Add Fifth Variable (APP_ENV)
```
Name: APP_ENV
Value: production
```
Click: **"Add"**

---

## 📝 Your db4free Credentials

Find these from your db4free account:

1. Go to: **https://db4free.net**
2. Login with your account
3. Find your database
4. Copy:
   - Host: `db4free.net`
   - Database: `sarap_local`
   - Username: `sarap_user`
   - Password: (your password)

---

## 🔄 Update Your PHP Code to Use Environment Variables

Your `db.php` file should read these variables:

### Current db.php (Hardcoded)
```php
$host = 'db4free.net';
$user = 'sarap_user';
$password = 'your_password';
$database = 'sarap_local';
```

### Updated db.php (Using Environment Variables)
```php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'sarap_local';
```

---

## 📋 Complete List of Variables to Add

| Name | Value | Example |
|------|-------|---------|
| DB_HOST | db4free.net | db4free.net |
| DB_NAME | sarap_local | sarap_local |
| DB_USER | sarap_user | sarap_user |
| DB_PASSWORD | your_password | MyPassword123! |
| APP_ENV | production | production |

---

## ✅ Step-by-Step on Render

### Visual Guide

```
Render Dashboard
    ↓
Your Service (sarap-local)
    ↓
Settings tab
    ↓
Environment Variables section
    ↓
"Add Environment Variable" button
    ↓
Fill in Name and Value
    ↓
Click "Add"
    ↓
Repeat for each variable
```

---

## 🎯 Quick Copy-Paste Values

### Variable 1
```
Name: DB_HOST
Value: db4free.net
```

### Variable 2
```
Name: DB_NAME
Value: sarap_local
```

### Variable 3
```
Name: DB_USER
Value: sarap_user
```

### Variable 4
```
Name: DB_PASSWORD
Value: (your db4free password)
```

### Variable 5
```
Name: APP_ENV
Value: production
```

---

## 🔐 Security Note

**Never commit passwords to GitHub!**

Environment variables keep secrets safe:
- ✅ Passwords stored on Render
- ✅ Not in your code
- ✅ Not on GitHub
- ✅ Secure! 🔒

---

## 📝 After Adding Variables

### Update db.php

Your `db.php` should use these variables:

```php
<?php
// Read from environment variables
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'sarap_local';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");
?>
```

---

## 🚀 After Setting Variables

### Step 1: Save Variables
- Click: **"Save"** or **"Deploy"**
- Render will restart your app

### Step 2: Wait for Restart
- ⏳ Wait 1-2 minutes
- Your app will restart with new variables

### Step 3: Test Your App
- Go to your Render URL
- Try logging in
- Check if database works

---

## ✨ Summary

1. **Go to Render dashboard**
2. **Click your service**
3. **Go to Settings**
4. **Add Environment Variables** (see list above)
5. **Update db.php** to use variables
6. **Push to GitHub**
7. **Render auto-deploys**
8. **Your app uses environment variables!** ✅

---

## 🎯 Do This Now

1. **On Render Dashboard**:
   - Add 5 environment variables (see above)
   - Click "Save"

2. **Update db.php**:
   - Use `getenv()` to read variables
   - Push to GitHub

3. **Wait for restart**

4. **Test your app**

---

**Status**: ✅ ENVIRONMENT VARIABLES GUIDE COMPLETE
