# Backend Scripts

Utility scripts for database management and deployment.

## 📜 Available Scripts

### 🔐 `seed_admin.php`
**Purpose:** Create or update admin account with known credentials

**Usage:**
```bash
php backend/scripts/seed_admin.php
```

**Features:**
- ✅ Safe to run multiple times
- ✅ Interactive confirmation before updating
- ✅ Shows current admin status
- ✅ Perfect for resetting forgotten passwords

**When to use:**
- Setting up fresh database
- Forgot admin password
- Need to update admin credentials
- After database reset

---

### 🚀 `setup_database.php`
**Purpose:** Complete database setup for fresh installation

**Usage:**
```bash
php backend/scripts/setup_database.php
```

**What it does:**
- Creates admin account
- Initializes default settings
- Shows database statistics
- One-command deployment setup

**When to use:**
- First time deployment to InfinityFree
- Setting up staging environment
- After fresh database import
- Quick database initialization

---

### 🧹 `cleanup_database.php`
**Purpose:** Remove test data before production export

**Usage:**
```bash
php backend/scripts/cleanup_database.php
```

**What it removes:**
- Unverified test users
- Test accounts (emails with 'test', '@gmail.com')
- All workout history
- All API logs
- Expired tokens
- Profile images

**What it keeps:**
- Admin accounts
- Settings configuration

**When to use:**
- **BEFORE** exporting database for production
- Preparing for InfinityFree deployment
- Cleaning up development data
- Database maintenance

---

## 🎯 Common Workflows

### Workflow 1: Local Development Setup
```bash
# 1. Fresh database, need admin
php backend/scripts/seed_admin.php

# 2. Login and start developing
# Email: admin@fitsync.com
# Password: Admin@12345
```

### Workflow 2: Forgot Admin Password
```bash
# Reset admin password
php backend/scripts/seed_admin.php
# Type 'yes' when prompted
```

### Workflow 3: Deploying to InfinityFree

**On Local (Before Export):**
```bash
# 1. Clean test data
php backend/scripts/cleanup_database.php

# 2. Verify admin account
php backend/scripts/seed_admin.php

# 3. Export database via phpMyAdmin
# (Structure + Data)
```

**On InfinityFree (After Upload):**
```bash
# 1. Import database via phpMyAdmin

# 2. Run setup script
php backend/scripts/setup_database.php
# OR access via browser:
# https://yourdomain.infinityfreeapp.com/backend/scripts/setup_database.php

# 3. Test login

# 4. IMPORTANT: Delete setup script for security
rm backend/scripts/setup_database.php
```

### Workflow 4: Staging Environment Setup
```bash
# Quick setup for testing
php backend/scripts/setup_database.php
```

---

## 🔒 Security Notes

### Before Deployment:
1. **Change default password** in `seed_admin.php`:
   ```php
   $ADMIN_CONFIG = [
       'password' => 'YourStrongPassword!123',  // Change this!
   ];
   ```

2. **Review cleanup rules** in `cleanup_database.php` to ensure you keep the right data

### After Deployment:
1. **Delete or protect setup scripts:**
   ```bash
   rm backend/scripts/setup_database.php
   # OR protect with .htaccess
   ```

2. **Change admin password** via dashboard:
   - Login as admin
   - Go to Profile Settings
   - Update password

3. **Keep `seed_admin.php`** (for emergency password recovery)
   - But secure it with strong password in the file
   - Don't commit passwords to version control

---

## ⚠️ Important Notes

### DO:
- ✅ Edit passwords in scripts before committing
- ✅ Test scripts on local environment first
- ✅ Backup database before running cleanup
- ✅ Keep `seed_admin.php` for password recovery
- ✅ Delete `setup_database.php` after deployment

### DON'T:
- ❌ Commit actual passwords to Git
- ❌ Run `cleanup_database.php` on production
- ❌ Leave `setup_database.php` accessible after deployment
- ❌ Use default passwords in production
- ❌ Run scripts without reading them first

---

## 🆘 Troubleshooting

### "Database connection failed"
**Solution:** Check `backend/config/config.php` database credentials

### "Admin account not found"
**Solution:** Run `seed_admin.php` to create admin

### "Can't login after cleanup"
**Solution:** Run `seed_admin.php` to restore admin account

### "Permission denied"
**Solution:** 
```bash
chmod +x backend/scripts/*.php  # Linux/Mac
# Or run with: php script_name.php
```

---

## 📚 Related Documentation

- **DEPLOYMENT_GUIDE.md** - Complete deployment instructions
- **EMAIL_COMPLETE_GUIDE.md** - Email configuration
- **BREVO_PERFECT_SOLUTION.md** - Email service setup

---

**Remember:** Always test on staging before running on production! 🚀
