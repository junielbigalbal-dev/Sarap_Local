# Render Web Service Configuration
## Step-by-Step Setup

---

## ⚠️ Important: Change from Docker to PHP

Render auto-detected Docker, but we want to use **PHP** instead (simpler method).

---

## 🔧 Fill in These Fields

### 1. Name
```
Name: sarap-local
```
(This is your service name)

### 2. Project (Optional)
```
Leave blank or select a project
```

### 3. Environment / Language
**IMPORTANT: Change from Docker to PHP**

Click on: **"Docker"** dropdown
Select: **"PHP"** (NOT Docker!)

### 4. Branch
```
Branch: main
```
(Already correct)

### 5. Region
```
Region: Singapore (Southeast Asia)
```
(Already correct - good for your location)

### 6. Root Directory
```
Leave blank
```
(Your files are in root, not a subfolder)

---

## 📋 Complete Configuration

```
Name: sarap-local
Project: (leave blank)
Environment: PHP (CHANGE FROM DOCKER!)
Branch: main
Region: Singapore
Root Directory: (leave blank)
Plan: Free
```

---

## 🎯 Step-by-Step on Render

### Step 1: Change Environment to PHP
- Click the **"Docker"** dropdown
- Select **"PHP"** from the list
- This will change the configuration

### Step 2: Fill in Name
```
Name: sarap-local
```

### Step 3: Verify Branch
```
Branch: main
```
(Should already be set)

### Step 4: Verify Region
```
Region: Singapore (Southeast Asia)
```
(Should already be set)

### Step 5: Leave Root Directory Blank
```
Root Directory: (empty)
```

### Step 6: Scroll Down and Click "Create Web Service"
- Click the **"Create Web Service"** button
- Wait for deployment (2-5 minutes)

---

## ✅ After Clicking "Create Web Service"

### Render Will:
1. Build your PHP app
2. Deploy to their servers
3. Give you a URL like: `https://sarap-local.onrender.com`
4. Show deployment logs

### You Should See:
```
Building...
Deploying...
Deployment successful!
```

---

## 📝 Important Notes

### Why Change from Docker?
- ✅ Simpler setup
- ✅ Faster deployment
- ✅ No Docker files needed
- ✅ Works perfectly with PHP

### What Render Does with PHP
- Detects PHP files
- Sets up Nginx + PHP-FPM
- Runs your app
- Handles HTTPS automatically

---

## 🚀 Quick Checklist

- [ ] Changed Environment from "Docker" to "PHP"
- [ ] Name: `sarap-local`
- [ ] Branch: `main`
- [ ] Region: `Singapore`
- [ ] Root Directory: (blank)
- [ ] Clicked "Create Web Service"
- [ ] Waiting for deployment

---

## ⏳ Deployment Timeline

```
Click "Create Web Service"
    ↓
Render builds app (1-2 min)
    ↓
Render deploys (1-2 min)
    ↓
You get a URL (1 min)
    ↓
Total: 3-5 minutes
```

---

## ✨ After Deployment

### You'll Get:
```
Service URL: https://sarap-local.onrender.com
```

### Visit Your App:
1. Go to: `https://sarap-local.onrender.com`
2. You should see your **login page**
3. Try logging in
4. ✅ Success!

---

## 🆘 If Something Goes Wrong

### Check Logs
1. Go to Render dashboard
2. Click your service
3. Click **"Logs"** tab
4. Look for errors

### Common Issues

**"PHP not found"**
- Make sure you selected "PHP" environment
- Not "Docker"

**"index.php not found"**
- Make sure index.php exists in your repo root
- Not in a subfolder

**"Database connection error"**
- Add environment variables (next step)
- Make sure db4free database is running

---

## 📋 Summary

1. **Change Environment to PHP** (not Docker)
2. **Fill in Name**: `sarap-local`
3. **Verify Branch**: `main`
4. **Verify Region**: `Singapore`
5. **Leave Root Directory blank**
6. **Click "Create Web Service"**
7. **Wait 3-5 minutes**
8. **Visit your URL**
9. **See your app!** ✅

---

## 🎯 Do This Now

1. **Click the "Docker" dropdown**
2. **Select "PHP"**
3. **Fill in Name**: `sarap-local`
4. **Scroll down**
5. **Click "Create Web Service"**
6. **Wait for deployment**
7. **Visit your URL**

---

**Status**: ✅ RENDER WEB SERVICE CONFIGURATION GUIDE COMPLETE
