# Complete Render Deployment Setup Guide

## Step 1: Create a Cloud Database (Railway - Easiest)

### 1.1 Sign Up for Railway
1. Go to https://railway.app
2. Click "Start Project"
3. Sign up with GitHub (recommended)
4. Authorize Railway to access your GitHub

### 1.2 Create MySQL Database
1. Click "New Project"
2. Click "Provision New"
3. Search for "MySQL" and select it
4. Click "Add"
5. Wait for database to initialize (2-3 minutes)

### 1.3 Get Database Credentials
1. Click on the MySQL service
2. Go to "Connect" tab
3. Copy these values:
   - **DB_HOST**: Look for `MYSQL_HOST` (e.g., `containers-us-west-xxx.railway.app`)
   - **DB_PORT**: Usually `6603` (note this)
   - **DB_USER**: Look for `MYSQL_USER` (usually `root`)
   - **DB_PASSWORD**: Look for `MYSQL_PASSWORD`
   - **DB_NAME**: Look for `MYSQL_DB` (usually `railway`)

**Note:** If you see a connection string like:
```
mysql://root:password@host:port/database
```
Extract:
- Host: `host`
- User: `root`
- Password: `password`
- Database: `database`

---

## Step 2: Create Database Schema

### 2.1 Connect to Your Railway Database
Use MySQL Workbench or command line:

```bash
mysql -h your-host -P 6603 -u root -p
```

When prompted, enter your password.

### 2.2 Create Database and Tables
Run these SQL commands:

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS sarap_local;
USE sarap_local;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    profile_picture VARCHAR(255),
    bio TEXT,
    user_type ENUM('customer', 'vendor') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    vendor_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10, 2),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Alternative:** If your app has a migration script, run:
```bash
php run_migration.php
```

---

## Step 3: Deploy to Render

### 3.1 Sign Up for Render
1. Go to https://render.com
2. Click "Sign Up"
3. Sign up with GitHub (recommended)
4. Authorize Render to access your GitHub

### 3.2 Create Web Service
1. Click "New +" button (top right)
2. Select "Web Service"
3. Connect your GitHub repository:
   - Search for `Sarap_Local`
   - Click "Connect"
4. Fill in the form:

| Field | Value |
|-------|-------|
| **Name** | `sarap-local` |
| **Environment** | `Docker` |
| **Branch** | `main` |
| **Region** | `Singapore (Southeast Asia)` |
| **Instance Type** | `Starter` ($9/month) |

5. Click "Create Web Service"

### 3.3 Add Environment Variables
1. Wait for the service to be created
2. Go to "Environment" tab
3. Add these variables (click "Add Environment Variable"):

```
DB_HOST = your-railway-host.com
DB_NAME = sarap_local
DB_USER = root
DB_PASSWORD = your-railway-password
APP_ENV = production
```

**Get these values from Railway dashboard** (Step 1.3)

4. Click "Save"

### 3.4 Deploy
1. Go to "Deploys" tab
2. Click "Manual Deploy" → "Deploy latest commit"
3. Wait for build to complete (5-10 minutes)
4. Check logs for errors

---

## Step 4: Verify Deployment

### 4.1 Check Service Status
1. In Render dashboard, look for status indicator
2. Should show "Live" in green
3. Copy your service URL (e.g., `https://sarap-local.onrender.com`)

### 4.2 Test Application
1. Visit your URL in browser
2. Test these features:
   - **Login page** loads
   - **Sign up** works
   - **Database connection** (check for errors)
   - **Product listing** displays
   - **File uploads** work

### 4.3 Check Logs
If something fails:
1. Go to "Logs" tab in Render
2. Look for error messages
3. Common issues:
   - `Connection refused` → Database credentials wrong
   - `File not found` → Missing files in Git
   - `Port already in use` → Check Dockerfile port

---

## Step 5: Troubleshooting

### Database Connection Error
**Error:** `Connection refused` or `Unknown host`

**Fix:**
1. Verify DB_HOST is correct (copy from Railway)
2. Check DB_PASSWORD has no special characters (if it does, use quotes)
3. Test connection locally first:
   ```bash
   mysql -h your-host -u root -p
   ```

### Build Fails
**Error:** `Docker build failed`

**Fix:**
1. Check Render logs for specific error
2. Ensure all files are pushed to GitHub:
   ```bash
   git status
   git add .
   git commit -m "Fix"
   git push origin main
   ```
3. Trigger manual deploy in Render

### Application Crashes
**Error:** Service keeps restarting

**Fix:**
1. Check logs for PHP errors
2. Verify environment variables are set
3. Check database tables exist (Step 2.2)

---

## Step 6: Optional - Custom Domain

### Add Custom Domain
1. In Render dashboard, go to "Settings"
2. Click "Add Custom Domain"
3. Enter your domain (e.g., `sarap.com`)
4. Follow DNS instructions
5. Wait for SSL certificate (5-10 minutes)

---

## Quick Reference

### Railway Database Connection
```
Host: containers-us-west-xxx.railway.app
Port: 6603
User: root
Password: [from Railway]
Database: railway (or your custom name)
```

### Render Environment Variables
```
DB_HOST=containers-us-west-xxx.railway.app
DB_NAME=railway
DB_USER=root
DB_PASSWORD=your_password
APP_ENV=production
```

### Test Commands
```bash
# Test database connection
mysql -h your-host -P 6603 -u root -p

# View Render logs
# (Use Render dashboard)

# Trigger redeploy
# (Use Render dashboard → Manual Deploy)
```

---

## Support

### If Deployment Fails
1. **Check Render logs** → Logs tab
2. **Verify database** → Railway dashboard
3. **Test locally** → `docker build -t sarap .`
4. **Push changes** → `git push origin main`

### Useful Links
- Railway: https://railway.app
- Render: https://render.com
- MySQL Docs: https://dev.mysql.com/doc/

---

## Next Steps After Deployment

1. ✅ Test all features
2. ✅ Set up monitoring (Render provides logs)
3. ✅ Configure backups (Railway has automatic backups)
4. ✅ Add custom domain (optional)
5. ✅ Monitor costs ($9/month for Render + Railway database)
