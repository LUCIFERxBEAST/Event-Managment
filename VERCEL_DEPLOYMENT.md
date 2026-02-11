# Vercel Deployment Guide

## 🚀 Steps to Deploy to Vercel

### 1. Set Environment Variables in Vercel Dashboard

Go to: **Vercel Dashboard → Your Project → Settings → Environment Variables**

Add the following variables:

#### Database (Supabase)
```
DB_HOST = db.xxxxxxxxxxxxx.supabase.co
DB_PORT = 5432
DB_NAME = postgres
DB_USER = postgres
DB_PASSWORD = your_supabase_password
DB_TIMEZONE = Asia/Kolkata
```

#### Email (SMTP)
```
SMTP_HOST = smtp.gmail.com
SMTP_USER = your_email@gmail.com
SMTP_PASS = your_app_password
SMTP_PORT = 587
FROM_NAME = Hackathon Security Team
```

**IMPORTANT:** Set these for "Production", "Preview", and "Development" environments.

### 2. Commit and Push Your Code

```bash
git add .
git commit -m "Fix Vercel deployment"
git push
```

Vercel will automatically redeploy when you push to your connected branch.

### 3. Verify Deployment

After deployment:
- Visit your Vercel URL
- Check the deployment logs for any errors
- Test critical pages (login, register, dashboard)

---

## 🔧 Troubleshooting

### "Failed to open stream" errors
- Make sure all file paths use `__DIR__` for absolute paths
- Check that `config/env.php` is included correctly

### "Connection failed" errors
- Verify environment variables are set in Vercel Dashboard
- Check that DB_HOST and DB_PASSWORD are correct
- Make sure Supabase allows connections from Vercel's IP

### Environment variables not working
- Clear build cache: Settings → Clear Build Cache
- Redeploy: Deployments → Click "..." → Redeploy

---

## ✅ What's Been Fixed

- ✅ `config/env.php` now works with both local `.env` files and Vercel environment variables
- ✅ `vercel.json` routing updated for proper file handling
- ✅ Environment loader detects Vercel platform automatically

---

## 📝 Note About Sessions

Vercel is a serverless platform, so PHP sessions work differently:
- Each request might hit a different server instance
- Consider using database-backed sessions for production
- Or use Vercel's edge config/KV storage for session data

For now, standard PHP sessions will work for basic testing.
