# Visual Deployment Guide
## See Exactly What to Click

**Total Time**: 20 minutes  
**Difficulty**: Very Easy ⭐

---

## 🎬 STEP 1: GitHub Account

### Screen 1: GitHub Homepage
```
Visit: https://github.com

You'll see:
┌─────────────────────────────────┐
│ GitHub Logo                     │
│                                 │
│ [Sign up] [Sign in]             │
│                                 │
│ Build software better, together │
└─────────────────────────────────┘

👉 Click: [Sign up]
```

### Screen 2: Sign Up Form
```
You'll see a form with:
┌─────────────────────────────────┐
│ Email: [____________]           │
│ Password: [____________]        │
│ Username: [____________]        │
│                                 │
│ [Create account]                │
└─────────────────────────────────┘

👉 Fill in your details
👉 Click: [Create account]
```

### Screen 3: Verify Email
```
Check your email inbox
Look for: "GitHub Verification"
Click the link in the email
✅ Done!
```

---

## 🎬 STEP 2: Create Repository

### Screen 1: After Login
```
Top right corner:
┌─────────────────────────────────┐
│ [+] [🔔] [👤]                   │
└─────────────────────────────────┘

👉 Click: [+]
```

### Screen 2: Dropdown Menu
```
You'll see:
┌─────────────────────────────────┐
│ New repository                  │
│ New gist                        │
│ New organization                │
│ Import repository               │
└─────────────────────────────────┘

👉 Click: New repository
```

### Screen 3: Create Repository
```
You'll see a form:
┌─────────────────────────────────┐
│ Repository name:                │
│ [sarap-local]                   │
│                                 │
│ Description:                    │
│ [Food marketplace app]          │
│                                 │
│ ◉ Public  ○ Private             │
│                                 │
│ [Create repository]             │
└─────────────────────────────────┘

👉 Fill in as shown
👉 Make sure "Public" is selected
👉 Click: [Create repository]
```

### Screen 4: Get Repository URL
```
After creation, you'll see:
┌─────────────────────────────────┐
│ [Code ▼]                        │
│ https://github.com/...          │
│ sarap-local.git                 │
│                                 │
│ [Copy button]                   │
└─────────────────────────────────┘

👉 Click: [Copy button]
👉 Save the URL somewhere
```

---

## 🎬 STEP 3: Push Code (Command Prompt)

### Screen 1: Open Command Prompt
```
Windows:
1. Press: Windows Key + R
2. Type: cmd
3. Press: Enter

You'll see:
┌─────────────────────────────────┐
│ C:\Users\YourName>              │
│                                 │
└─────────────────────────────────┘
```

### Screen 2: Navigate to Project
```
Type this command:
cd C:\xampp\htdocs\sarap_local

Press: Enter

You'll see:
┌─────────────────────────────────┐
│ C:\xampp\htdocs\sarap_local>    │
│                                 │
└─────────────────────────────────┘
```

### Screen 3: Initialize Git
```
Type: git init
Press: Enter

You'll see:
┌─────────────────────────────────┐
│ Initialized empty Git repository│
│                                 │
└─────────────────────────────────┘
```

### Screen 4: Configure Git
```
Type: git config user.name "Your Name"
Press: Enter

Type: git config user.email "your@email.com"
Press: Enter

(No output = success)
```

### Screen 5: Add Files
```
Type: git add .
Press: Enter

(No output = success)
```

### Screen 6: Create Commit
```
Type: git commit -m "Initial commit"
Press: Enter

You'll see:
┌─────────────────────────────────┐
│ [main (root-commit) abc1234]    │
│ X files changed, Y insertions   │
└─────────────────────────────────┘
```

### Screen 7: Connect to GitHub
```
Type: git remote add origin [YOUR_GITHUB_URL]
Press: Enter

(Replace [YOUR_GITHUB_URL] with your copied URL)
```

### Screen 8: Push to GitHub
```
Type: git branch -M main
Press: Enter

Type: git push -u origin main
Press: Enter

You'll see:
┌─────────────────────────────────┐
│ Enumerating objects...          │
│ Counting objects...             │
│ Compressing objects...          │
│ Writing objects...              │
│ [main (root-commit) abc1234]    │
│ X files changed                 │
└─────────────────────────────────┘

✅ Done!
```

---

## 🎬 STEP 4: Render Account

### Screen 1: Render Homepage
```
Visit: https://render.com

You'll see:
┌─────────────────────────────────┐
│ Render Logo                     │
│                                 │
│ [Get Started]                   │
│                                 │
│ Modern cloud platform...        │
└─────────────────────────────────┘

👉 Click: [Get Started]
```

### Screen 2: Sign Up
```
You'll see:
┌─────────────────────────────────┐
│ [Sign up with GitHub]           │
│ [Sign up with Google]           │
│ [Sign up with Email]            │
└─────────────────────────────────┘

👉 Click: [Sign up with GitHub]
```

### Screen 3: Authorize
```
GitHub will ask:
"Authorize Render?"

👉 Click: [Authorize render]
```

### Screen 4: Dashboard
```
After login, you'll see:
┌─────────────────────────────────┐
│ Dashboard                       │
│                                 │
│ [New +]                         │
│                                 │
│ (empty - no services yet)       │
└─────────────────────────────────┘

✅ Account created!
```

---

## 🎬 STEP 5: Deploy on Render

### Screen 1: Create Service
```
On dashboard:
┌─────────────────────────────────┐
│ [New +]                         │
└─────────────────────────────────┘

👉 Click: [New +]
```

### Screen 2: Choose Service Type
```
You'll see:
┌─────────────────────────────────┐
│ Web Service                     │
│ Static Site                     │
│ PostgreSQL                      │
│ MySQL                           │
│ Redis                           │
└─────────────────────────────────┘

👉 Click: Web Service
```

### Screen 3: Connect GitHub
```
You'll see:
┌─────────────────────────────────┐
│ [Connect account]               │
│                                 │
│ Or select existing repo         │
└─────────────────────────────────┘

👉 Click: [Connect account]
```

### Screen 4: Select Repository
```
You'll see your repos:
┌─────────────────────────────────┐
│ ☑ sarap-local                   │
│ ○ other-repo                    │
│                                 │
│ [Connect]                       │
└─────────────────────────────────┘

👉 Click: sarap-local
👉 Click: [Connect]
```

### Screen 5: Configure Service
```
You'll see a form:
┌─────────────────────────────────┐
│ Name: sarap-local               │
│ Environment: PHP                │
│ Build Command: (empty)          │
│ Start Command: (empty)          │
│ Plan: Free                      │
│                                 │
│ [Create Web Service]            │
└─────────────────────────────────┘

👉 Fill as shown
👉 Click: [Create Web Service]
```

### Screen 6: Deployment
```
You'll see:
┌─────────────────────────────────┐
│ Deploying...                    │
│ ████████░░░░░░░░░░░░ 50%       │
│                                 │
│ Building...                     │
│ Pushing...                      │
│ Starting...                     │
└─────────────────────────────────┘

⏳ Wait 2-5 minutes...
```

### Screen 7: Success!
```
You'll see:
┌─────────────────────────────────┐
│ ✅ Deployment successful        │
│                                 │
│ https://sarap-local.onrender.com│
│                                 │
│ [Visit Site]                    │
└─────────────────────────────────┘

👉 Click: [Visit Site]
✅ Your app is LIVE!
```

---

## 🎬 STEP 6: Database Setup

### Screen 1: db4free.net
```
Visit: https://db4free.net

You'll see:
┌─────────────────────────────────┐
│ db4free Logo                    │
│                                 │
│ [Sign up]                       │
│                                 │
│ Free MySQL hosting              │
└─────────────────────────────────┘

👉 Click: [Sign up]
```

### Screen 2: Sign Up Form
```
Fill in:
┌─────────────────────────────────┐
│ Email: [your@email.com]         │
│ Password: [____________]        │
│ Confirm: [____________]         │
│                                 │
│ [Create Account]                │
└─────────────────────────────────┘

👉 Fill in details
👉 Click: [Create Account]
```

### Screen 3: Create Database
```
After login:
┌─────────────────────────────────┐
│ [Create Database]               │
└─────────────────────────────────┘

👉 Click: [Create Database]
```

### Screen 4: Database Details
```
Fill in:
┌─────────────────────────────────┐
│ Database name: sarap_local      │
│ Username: sarap_user            │
│ Password: [your_password]       │
│                                 │
│ [Create]                        │
└─────────────────────────────────┘

👉 Fill in details
👉 Click: [Create]
```

### Screen 5: Get Credentials
```
You'll see:
┌─────────────────────────────────┐
│ Host: db4free.net               │
│ Database: sarap_local           │
│ Username: sarap_user            │
│ Password: ****                  │
│                                 │
│ [Copy]                          │
└─────────────────────────────────┘

👉 Save these details!
```

---

## 🎬 STEP 7: Update Code

### Screen 1: Edit db.php
```
Open: C:\xampp\htdocs\sarap_local\db.php

Find these lines:
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'sarap_local';

Replace with:
$host = 'db4free.net';
$user = 'sarap_user';
$password = 'your_password';
$database = 'sarap_local';

Save the file
```

### Screen 2: Push Changes
```
Command Prompt:

Type: cd C:\xampp\htdocs\sarap_local
Press: Enter

Type: git add db.php
Press: Enter

Type: git commit -m "Update database"
Press: Enter

Type: git push origin main
Press: Enter

✅ Render auto-deploys!
```

---

## 🎉 FINAL RESULT

### Your App is LIVE!
```
URL: https://sarap-local.onrender.com

Features:
✅ Login page
✅ Customer dashboard
✅ Vendor dashboard
✅ Product management
✅ Reel uploads
✅ Order system
✅ Mobile responsive
✅ Database connected
```

---

## 📝 Summary

You've completed:
1. ✅ GitHub account
2. ✅ GitHub repository
3. ✅ Pushed code
4. ✅ Render account
5. ✅ Deployed app
6. ✅ Database setup
7. ✅ **App is LIVE!**

---

**Status**: ✅ VISUAL GUIDE COMPLETE
