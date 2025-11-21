# Deploy to Render Using GitHub
## Complete Step-by-Step Guide

**Date**: November 21, 2025  
**Status**: ✅ READY

---

## 🎯 Overview

**Render** is perfect for deploying PHP apps with:
- ✅ Free tier available
- ✅ Automatic HTTPS
- ✅ Easy GitHub integration
- ✅ PostgreSQL/MySQL support
- ✅ Auto-deploy on push
- ✅ No credit card needed (free tier)

---

## 📋 Prerequisites

- [ ] GitHub account (free at github.com)
- [ ] Render account (free at render.com)
- [ ] Git installed on your computer
- [ ] Your Sarap Local project files

---

## 🔧 Step 1: Install Git (If Not Already Installed)

### Windows:
```
1. Go to git-scm.com
2. Download Git for Windows
3. Run installer
4. Accept all defaults
5. Click "Finish"
```

### Mac:
```bash
# Using Homebrew
brew install git

# Or download from git-scm.com
```

### Linux:
```bash
sudo apt install git
```

---

## 📤 Step 2: Create GitHub Repository

### 2.1: Create GitHub Account
```
1. Go to github.com
2. Click "Sign up"
3. Enter email, password, username
4. Verify email
5. Done!
```

### 2.2: Create New Repository
```
1. Go to github.com
2. Click "+" icon (top right)
3. Click "New repository"
4. Name: sarap-local
5. Description: "Food marketplace app"
6. Choose "Public" (for free tier)
7. Click "Create repository"
```

### 2.3: Get Repository URL
```
1. On your new repository page
2. Click green "Code" button
3. Copy HTTPS URL
4. It looks like: https://github.com/YOUR_USERNAME/sarap-local.git
```

---

## 💻 Step 3: Push Code to GitHub

### 3.1: Open Command Prompt/Terminal

**Windows**: 
- Press `Win + R`
- Type `cmd`
- Press Enter

**Mac/Linux**:
- Open Terminal

### 3.2: Navigate to Your Project
```bash
cd C:\xampp\htdocs\sarap_local
```

### 3.3: Initialize Git Repository
```bash
git init
git config user.name "Your Name"
git config user.email "your.email@example.com"
```

### 3.4: Add All Files
```bash
git add .
```

### 3.5: Create First Commit
```bash
git commit -m "Initial commit - Sarap Local food marketplace"
```

### 3.6: Add Remote Repository
```bash
git remote add origin https://github.com/YOUR_USERNAME/sarap-local.git
```

### 3.7: Push to GitHub
```bash
git branch -M main
git push -u origin main
```

**Wait for upload to complete** (might take a few minutes)

### 3.8: Verify on GitHub
```
1. Go to github.com/YOUR_USERNAME/sarap-local
2. You should see all your files
3. Success! ✅
```

---

## 🚀 Step 4: Deploy on Render

### 4.1: Create Render Account
```
1. Go to render.com
2. Click "Get Started"
3. Click "Sign up with GitHub"
4. Authorize Render
5. Done!
```

### 4.2: Create New Web Service
```
1. Go to render.com/dashboard
2. Click "New +"
3. Click "Web Service"
4. Click "Connect account" (GitHub)
5. Select your "sarap-local" repository
6. Click "Connect"
```

### 4.3: Configure Service
```
Name: sarap-local
Environment: PHP
Build Command: (leave empty)
Start Command: (leave empty)
Plan: Free (for testing)
```

### 4.4: Set Environment Variables
```
1. Scroll to "Environment"
2. Click "Add Environment Variable"
3. Add these variables:

DB_HOST = localhost
DB_USER = sarap_user
DB_PASSWORD = your_password
DB_NAME = sarap_local
```

### 4.5: Deploy
```
1. Click "Create Web Service"
2. Wait for deployment (2-5 minutes)
3. You'll see a URL like: https://sarap-local.onrender.com
4. Click the URL to visit your app!
```

---

## 🗄️ Step 5: Setup Database on Render

### 5.1: Create MySQL Database

**Option A: Using Render's Database**
```
1. Go to render.com/dashboard
2. Click "New +"
3. Click "MySQL"
4. Name: sarap-local-db
5. Plan: Free
6. Click "Create Database"
7. Copy connection details
```

**Option B: Using External Service (Recommended)**
```
1. Go to db4free.net
2. Sign up
3. Create new database
4. Name: sarap_local
5. Copy connection details
6. Update db.php with these details
```

### 5.2: Import Database Schema
```bash
# Download your database backup
# Then upload it to your database service

# Or use phpMyAdmin if available
```

### 5.3: Update db.php
```php
// In your sarap_local/db.php, update:

$host = 'your-db-host.db4free.net';
$user = 'your_db_user';
$password = 'your_db_password';
$database = 'sarap_local';
```

### 5.4: Push Changes to GitHub
```bash
git add db.php
git commit -m "Update database configuration for production"
git push origin main
```

**Render will auto-deploy!** ✅

---

## 📁 Step 6: Configure File Uploads

### 6.1: Create Upload Directories
```bash
# SSH into Render (if available)
# Or create directories manually

mkdir -p uploads/products
mkdir -p uploads/reels
mkdir -p uploads/vendor_ids
chmod -R 777 uploads/
```

### 6.2: Update Upload Paths
```php
// In your code, ensure upload paths are correct:
$upload_dir = 'uploads/products/';
$upload_dir = 'uploads/reels/';
```

---

## ✅ Step 7: Verify Deployment

### Test Your App
```
1. Go to your Render URL
2. Test login page
3. Try logging in
4. Test customer features
5. Test vendor features
6. Test file uploads
7. Test on mobile
```

### Check Logs
```
1. Go to render.com/dashboard
2. Click your service
3. Click "Logs" tab
4. Check for errors
```

---

## 🔄 Step 8: Auto-Deploy on Changes

### How It Works
```
1. You make changes locally
2. Commit: git commit -m "message"
3. Push: git push origin main
4. GitHub receives changes
5. Render automatically deploys
6. Your app updates instantly!
```

### Example Workflow
```bash
# Make changes to your code
nano vendor.php

# Stage changes
git add vendor.php

# Commit
git commit -m "Fix vendor dashboard"

# Push to GitHub
git push origin main

# Render auto-deploys! ✅
```

---

## 🆘 Troubleshooting

### "Build Failed"
```
1. Check Render logs
2. Look for error messages
3. Common issues:
   - Missing files
   - Wrong file paths
   - Database connection error
4. Fix locally
5. Push to GitHub
6. Render will retry
```

### "Database Connection Error"
```
1. Verify db.php credentials
2. Check database is running
3. Test connection locally first
4. Update environment variables
5. Restart service on Render
```

### "File Upload Not Working"
```
1. Check upload directory permissions
2. Verify directory exists
3. Check file path is correct
4. Ensure uploads/ is writable
```

### "Page Shows Blank"
```
1. Check Render logs
2. Look for PHP errors
3. Verify database connection
4. Check file permissions
```

---

## 📊 Render Pricing

| Feature | Free | Starter | Pro |
|---------|------|---------|-----|
| **Cost** | $0 | $7/mo | $12/mo |
| **Uptime** | 99% | 99.9% | 99.99% |
| **Auto-sleep** | Yes | No | No |
| **Database** | Limited | Included | Included |
| **Best For** | Testing | Production | Enterprise |

---

## 🎯 Your Deployment URL

After deployment, you'll have:
```
https://sarap-local.onrender.com
```

Share this URL with users!

---

## 📋 Complete Checklist

- [ ] GitHub account created
- [ ] Repository created
- [ ] Code pushed to GitHub
- [ ] Render account created
- [ ] Web service created
- [ ] Database configured
- [ ] db.php updated
- [ ] Environment variables set
- [ ] Deployment successful
- [ ] App accessible via URL
- [ ] Login works
- [ ] Features tested
- [ ] Mobile tested

---

## 🚀 Quick Reference

### Push Updates to Production
```bash
# Make changes
git add .
git commit -m "Your message"
git push origin main
# Done! Render auto-deploys
```

### View Logs
```
1. Go to render.com/dashboard
2. Click your service
3. Click "Logs"
```

### Restart Service
```
1. Go to render.com/dashboard
2. Click your service
3. Click "Manual Deploy"
4. Click "Deploy latest commit"
```

---

## 💡 Pro Tips

1. **Use .gitignore** - Don't push sensitive files
2. **Environment Variables** - Store passwords in Render, not code
3. **Regular Backups** - Backup database regularly
4. **Monitor Logs** - Check logs for errors
5. **Test Locally** - Always test before pushing

---

## 📞 Support

### Render Support
- Docs: render.com/docs
- Status: status.render.com
- Help: render.com/support

### GitHub Support
- Docs: docs.github.com
- Help: github.com/support

---

## 🎉 You're Live!

After following these steps:
- ✅ Your app is live on the internet
- ✅ Accessible from anywhere
- ✅ Auto-deploys on code changes
- ✅ Mobile-friendly
- ✅ Secure with HTTPS

**Congratulations! Your app is deployed!** 🚀

---

**Status**: ✅ GITHUB + RENDER DEPLOYMENT COMPLETE
