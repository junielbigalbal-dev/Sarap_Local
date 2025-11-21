# Complete Step-by-Step Deployment Guide for Sarap Local

## PART 1: CREATE RAILWAY ACCOUNT & DATABASE (20 minutes)

### Step 1.1: Sign Up for Railway
1. Open your browser
2. Go to: **https://railway.app**
3. Click the **"Start Project"** button (top right)
4. Click **"Sign up with GitHub"**
5. You'll be redirected to GitHub
6. Click **"Authorize railway-app"** to allow Railway to access your GitHub
7. You'll be redirected back to Railway - you're now signed up!

### Step 1.2: Create a New Project
1. You should see a dashboard with **"New Project"** button
2. Click **"New Project"**
3. Click **"Provision New"** (or **"Add Service"**)
4. A list of services will appear

### Step 1.3: Add MySQL Database
1. Search for **"MySQL"** in the services list
2. Click on **"MySQL"**
3. Click **"Add"** or **"Deploy"**
4. Wait 2-3 minutes - you'll see a loading indicator
5. Once done, you'll see a green checkmark

### Step 1.4: Get Your Database Credentials
1. Click on the **"MySQL"** service box
2. Look for a **"Connect"** tab or button
3. You should see something like this:
   ```
   MYSQL_HOST: containers-us-west-xxx.railway.app
   MYSQL_PORT: 6603
   MYSQL_USER: root
   MYSQL_PASSWORD: [a random password]
   MYSQL_DB: railway
   ```

4. **Copy and save these values somewhere safe** (Notepad):
   - **Host**: `containers-us-west-xxx.railway.app`
   - **Port**: `6603`
   - **User**: `root`
   - **Password**: `[copy the password]`
   - **Database**: `railway`

---

## PART 2: CREATE DATABASE TABLES (10 minutes)

### Step 2.1: Download MySQL Workbench (if you don't have it)
1. Go to: **https://dev.mysql.com/downloads/workbench/**
2. Download and install MySQL Workbench
3. Open it after installation

### Step 2.2: Connect to Your Railway Database
1. In MySQL Workbench, click **"+"** next to "MySQL Connections"
2. Fill in the connection details:
   - **Connection Name**: `Railway Sarap`
   - **Hostname**: Paste your `MYSQL_HOST` from Step 1.4
   - **Port**: `6603`
   - **Username**: `root`
   - **Password**: Click "Store in Vault" and paste your `MYSQL_PASSWORD`
3. Click **"Test Connection"** - should say "Successfully made the MySQL connection"
4. Click **"OK"**

### Step 2.3: Create Database Tables
1. Double-click your new connection to open it
2. You should see a query editor (blank area)
3. Copy and paste this SQL code:

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

-- Reels table (if you have video/reel feature)
CREATE TABLE IF NOT EXISTS reels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    title VARCHAR(255),
    video_url VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
);
```

4. Select all the code (Ctrl+A)
5. Click the **lightning bolt icon** (Execute) or press **Ctrl+Enter**
6. You should see "Query executed successfully" messages
7. Done! Your database is ready

---

## PART 3: DEPLOY TO RENDER (15 minutes)

### Step 3.1: Sign Up for Render
1. Open your browser
2. Go to: **https://render.com**
3. Click **"Sign up"** (top right)
4. Click **"Sign up with GitHub"**
5. You'll be redirected to GitHub
6. Click **"Authorize render-oss"**
7. You're now signed up on Render!

### Step 3.2: Create a Web Service
1. In Render dashboard, click **"New +"** button (top right)
2. Click **"Web Service"**
3. You'll see a list of your GitHub repos
4. Find **"Sarap_Local"** and click **"Connect"**
5. If you don't see it, click "Configure account" and authorize Render to see all repos

### Step 3.3: Fill in Deployment Settings
Fill in these fields exactly:

| Field | Value |
|-------|-------|
| **Name** | `sarap-local` |
| **Environment** | `Docker` |
| **Branch** | `main` |
| **Region** | `Singapore (Southeast Asia)` |
| **Instance Type** | `Starter` ($9/month) |

**Leave other fields as default**

4. Click **"Create Web Service"**
5. Wait for the service to be created (1-2 minutes)

### Step 3.4: Add Environment Variables
1. Once the service is created, you'll see a dashboard
2. Click the **"Environment"** tab (on the left side)
3. Click **"Add Environment Variable"** button
4. Add these 5 variables one by one:

**Variable 1:**
- Key: `DB_HOST`
- Value: Paste your `MYSQL_HOST` from Step 1.4 (e.g., `containers-us-west-xxx.railway.app`)
- Click "Add"

**Variable 2:**
- Key: `DB_NAME`
- Value: `sarap_local`
- Click "Add"

**Variable 3:**
- Key: `DB_USER`
- Value: `root`
- Click "Add"

**Variable 4:**
- Key: `DB_PASSWORD`
- Value: Paste your `MYSQL_PASSWORD` from Step 1.4
- Click "Add"

**Variable 5:**
- Key: `APP_ENV`
- Value: `production`
- Click "Add"

5. All variables should now be listed
6. Click **"Save"** (if there's a save button)

### Step 3.5: Deploy Your App
1. Go to the **"Deploys"** tab
2. Click **"Manual Deploy"** → **"Deploy latest commit"**
3. You'll see a build log starting
4. Wait for the build to complete (5-10 minutes)
5. You should see **"Your service is live"** message
6. Copy your service URL (looks like: `https://sarap-local.onrender.com`)

---

## PART 4: TEST YOUR DEPLOYMENT (5 minutes)

### Step 4.1: Visit Your Live App
1. Copy the URL from Step 3.5
2. Open it in a new browser tab
3. You should see your Sarap Local app!

### Step 4.2: Test Key Features
1. **Test Login Page**
   - Does the login page load?
   - Try signing up with a test account

2. **Test Database Connection**
   - After login, do you see products?
   - Can you browse the app without errors?

3. **Test File Uploads** (if applicable)
   - Try uploading a profile picture or product image
   - Does it save?

### Step 4.3: Check Logs for Errors
If something doesn't work:
1. Go to Render dashboard
2. Click **"Logs"** tab
3. Look for red error messages
4. Common errors:
   - `Connection refused` → Database credentials wrong
   - `File not found` → Missing files in Git
   - `Port already in use` → Restart service

---

## PART 5: TROUBLESHOOTING

### Problem: "Service is not responding"
**Solution:**
1. Check Render logs for errors
2. Verify all 5 environment variables are set correctly
3. Restart the service: Render dashboard → "Settings" → "Restart"

### Problem: "Database connection error"
**Solution:**
1. Double-check your database credentials from Railway
2. Make sure you copied them exactly (no extra spaces)
3. Test the connection in MySQL Workbench again

### Problem: "Build failed"
**Solution:**
1. Check Render build logs
2. Make sure all files are pushed to GitHub:
   ```bash
   git status
   git add .
   git commit -m "Fix deployment"
   git push origin main
   ```
3. Trigger manual deploy again in Render

### Problem: "Page loads but shows errors"
**Solution:**
1. Check Render logs for PHP errors
2. Verify database tables were created (Step 2.3)
3. Make sure APP_ENV is set to `production`

---

## QUICK REFERENCE

### Railway Database Info
```
Host: containers-us-west-xxx.railway.app
Port: 6603
User: root
Password: [from Railway]
Database: railway → change to sarap_local
```

### Render Environment Variables
```
DB_HOST=containers-us-west-xxx.railway.app
DB_NAME=sarap_local
DB_USER=root
DB_PASSWORD=your_password
APP_ENV=production
```

### Your Live App URL
```
https://sarap-local.onrender.com
```

---

## SUMMARY

✅ **Step 1:** Create Railway account & MySQL database (20 min)
✅ **Step 2:** Create database tables (10 min)
✅ **Step 3:** Deploy to Render (15 min)
✅ **Step 4:** Test your app (5 min)
✅ **Total Time: ~50 minutes**

**Your app will be live at: https://sarap-local.onrender.com**

---

## NEXT STEPS AFTER DEPLOYMENT

1. Monitor your app in Render dashboard
2. Check logs regularly for errors
3. Test all features thoroughly
4. Set up custom domain (optional)
5. Configure backups (Railway does this automatically)

**Good luck! 🚀**
