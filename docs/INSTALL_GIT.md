# Install Git - Fix the Error
## 'git' is not recognized

---

## ❌ The Error You Got

```
'git' is not recognized as an internal or external command,
operable program or batch file
```

## ✅ What This Means

**Git is not installed on your computer!**

Git is a program you need to install first.

---

## 🔧 How to Install Git (Windows)

### Step 1: Download Git

1. Open your browser
2. Go to: **https://git-scm.com/download/win**
3. Click the download button
4. A file will download (looks like: `Git-2.x.x-64-bit.exe`)

### Step 2: Run the Installer

1. Find the downloaded file
2. Double-click it
3. Click **"Yes"** if Windows asks for permission

### Step 3: Follow Installation Steps

You'll see a setup wizard. Just click **"Next"** for everything:

```
Screen 1: License
  👉 Click: [Next]

Screen 2: Installation folder
  👉 Click: [Next]

Screen 3: Components
  👉 Click: [Next]

Screen 4: Start menu folder
  👉 Click: [Next]

Screen 5: Default editor
  👉 Click: [Next]

Screen 6: Initial branch name
  👉 Click: [Next]

Screen 7: PATH environment
  👉 Click: [Next]

Screen 8: HTTPS transport backend
  👉 Click: [Next]

Screen 9: Line ending conversions
  👉 Click: [Next]

Screen 10: Terminal emulator
  👉 Click: [Next]

Screen 11: Default pull behavior
  👉 Click: [Next]

Screen 12: Credential manager
  👉 Click: [Next]

Screen 13: Extra options
  👉 Click: [Next]

Screen 14: Experimental options
  👉 Click: [Next]

Screen 15: Installing...
  ⏳ Wait for installation

Screen 16: Finish
  👉 Click: [Finish]
```

### Step 4: Restart Command Prompt

1. Close Command Prompt (if it's open)
2. Open Command Prompt again:
   - Press **Windows Key + R**
   - Type: `cmd`
   - Press **Enter**

### Step 5: Verify Git is Installed

Type this command:
```bash
git --version
```

Press **Enter**

**You should see**:
```
git version 2.x.x (or similar)
```

✅ **Git is installed!**

---

## 🍎 How to Install Git (Mac)

### Option 1: Using Homebrew (Easiest)

1. Open Terminal
2. Type this command:
```bash
brew install git
```
3. Press **Enter**
4. Wait for installation

### Option 2: Download from Website

1. Go to: **https://git-scm.com/download/mac**
2. Download the installer
3. Double-click to run
4. Follow the installation steps

### Step 3: Verify Installation

Type:
```bash
git --version
```

Press **Enter**

You should see the version number ✅

---

## 🐧 How to Install Git (Linux)

### Ubuntu/Debian

```bash
sudo apt update
sudo apt install git
```

### Fedora/CentOS

```bash
sudo yum install git
```

### Verify Installation

```bash
git --version
```

---

## ✅ After Installing Git

### Go Back to Step 3

Now that Git is installed, you can continue with Step 3:

1. Open Command Prompt
2. Type:
```bash
cd C:\xampp\htdocs\sarap_local
```
3. Press **Enter**
4. Now try the git commands again!

---

## 🔄 Complete Step 3 Commands (After Git Install)

```bash
cd C:\xampp\htdocs\sarap_local
git init
git config user.name "Your Name"
git config user.email "your@email.com"
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/sarap-local.git
git branch -M main
git push -u origin main
```

---

## ✨ What Git Does

**Git** = A program that:
- Tracks changes to your code
- Uploads code to GitHub
- Manages versions of your files

**You need Git to upload to GitHub!**

---

## 🎯 Summary

1. ✅ Download Git from git-scm.com
2. ✅ Run the installer
3. ✅ Click "Next" for everything
4. ✅ Restart Command Prompt
5. ✅ Verify with: `git --version`
6. ✅ Go back to Step 3

---

## 🆘 Still Getting the Error?

### Try This

1. **Restart your computer** (important!)
2. **Open Command Prompt again**
3. **Type**: `git --version`
4. **Should work now!**

If still not working:
- Make sure you installed Git correctly
- Try restarting your computer
- Download Git again from git-scm.com

---

## 📝 Checklist

- [ ] Downloaded Git from git-scm.com
- [ ] Ran the installer
- [ ] Clicked "Next" through all steps
- [ ] Restarted Command Prompt
- [ ] Typed: `git --version`
- [ ] Saw version number
- [ ] Ready for Step 3!

---

**Status**: ✅ GIT INSTALLATION GUIDE COMPLETE
