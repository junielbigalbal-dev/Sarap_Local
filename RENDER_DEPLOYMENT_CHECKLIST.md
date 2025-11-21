# Render Deployment Checklist

## ✅ Configuration Updates Complete

### Files Updated
- ✅ `Dockerfile` - Updated to port 8080, added health check, proper permissions
- ✅ `render.yaml` - Upgraded to Starter plan, added environment variables
- ✅ `default.conf` - Changed to port 8080
- ✅ `.dockerignore` - Created to optimize build
- ✅ `.env.example` - Created for reference

## 📋 Pre-Deployment Steps

### 1. Set Up Cloud Database
You **MUST** have a cloud database before deploying. Options:
- **Railway** (easiest): https://railway.app
- **Render PostgreSQL**: Built into Render
- **AWS RDS**: More complex but scalable
- **PlanetScale**: MySQL-compatible

**Action**: Create a MySQL database and note:
- Host (e.g., `mysql.railway.internal`)
- Database name
- Username
- Password

### 2. Push Changes to GitHub
```bash
git add .
git commit -m "Update Docker config for Render deployment"
git push origin main
```

### 3. Configure Render Environment Variables
In Render Dashboard → Environment:
```
DB_HOST=your-database-host
DB_NAME=sarap_local
DB_USER=your_username
DB_PASSWORD=your_password
APP_ENV=production
```

### 4. Deploy on Render
1. Go to https://render.com
2. Click "New +" → "Web Service"
3. Connect your GitHub repo
4. Select branch: `main`
5. Verify settings:
   - Name: `sarap-local`
   - Language: Docker
   - Region: Singapore
   - Plan: Starter ($9/month)
6. Click "Deploy Web Service"

## 🔍 Post-Deployment Verification

### Check Deployment Status
- Monitor logs in Render Dashboard
- Look for "Service is live" message

### Test Application
1. Visit your service URL (e.g., `https://sarap-local.onrender.com`)
2. Test login functionality
3. Test database operations (products, uploads)
4. Check file uploads work

### Troubleshooting
If deployment fails:
1. Check build logs in Render Dashboard
2. Verify database credentials are correct
3. Ensure database is accessible from Render
4. Check that all required files are in Git

## 💾 Important Notes

- **Free instances spin down** after 15 minutes of inactivity
- **Starter plan ($9/month)** recommended for production
- **Persistent storage**: Uploads are stored in container (ephemeral)
  - Consider using cloud storage (AWS S3, Cloudinary) for production
- **Database**: Must be external (not local XAMPP)

## 📞 Support

If you encounter issues:
1. Check Render logs: Dashboard → Logs
2. Verify environment variables are set
3. Test database connection separately
4. Review `db.php` error handling
