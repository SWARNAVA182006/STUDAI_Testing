# MySQL Password Setup for Laravel Herd

## Current Issue
MySQL requires authentication but we don't have the root password.

## Solutions (Choose One)

### Option 1: Find Herd's MySQL Credentials (Recommended)

1. **Open Laravel Herd Application**
   - Press Windows key
   - Type "Laravel Herd"
   - Open the application

2. **Check MySQL Settings**
   - Look for MySQL/Database settings in Herd
   - Find the default root password
   - Common defaults:
     - Password: `` (empty)
     - Password: `root`
     - Password: `password`
     - Password: `herd`

3. **Update .env file**
   ```
   DB_PASSWORD=the_password_you_found
   ```

### Option 2: Reset MySQL Root Password via Herd

If Herd has a database management tool or phpMyAdmin:

1. Open Herd's database manager
2. Login with any existing credentials
3. Run this SQL:
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'StudAI2025!';
   FLUSH PRIVILEGES;
   ```
4. Update .env:
   ```
   DB_PASSWORD=StudAI2025!
   ```

### Option 3: Use Command Line (If MySQL CLI is accessible)

Try to find mysql.exe in Herd:
```powershell
# Search for mysql.exe
Get-ChildItem "C:\Users\$env:USERNAME\.config\herd" -Recurse -Include mysql.exe

# Or check common locations:
# C:\Users\USERNAME\.config\herd\bin\mysql.exe
# C:\Users\USERNAME\AppData\Local\Herd\bin\mysql.exe
```

Once found, try connecting with common passwords:
```bash
mysql -u root -p
# Try: (empty), root, password, herd
```

### Option 4: Fresh MySQL Installation (Last Resort)

If Herd's MySQL is problematic, install standalone MySQL:

1. Download MySQL: https://dev.mysql.com/downloads/installer/
2. Install with password: `StudAI2025!`
3. Update .env:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_USERNAME=root
   DB_PASSWORD=StudAI2025!
   ```

---

## Once Password is Set

1. Update both database connections in `.env`:
   ```
   DB_PASSWORD=your_password_here
   DB_PASSWORD_ANALYTICS=your_password_here
   ```

2. Test connection:
   ```bash
   php database\test-connection.php
   ```

3. If successful, you'll see:
   ```
   ✓ SUCCESS! Connected to MySQL
   ✓ studai_career created
   ✓ studai_career_analytics created
   Databases ready! You can now run migrations.
   ```

---

## Next Steps After Database Setup

1. ✅ Verify connection works
2. ✅ Run migrations: `php artisan migrate`
3. ✅ Install Laravel Breeze
4. ✅ Continue with Phase 1

---

**What would you like to do?**
1. Check Herd application for MySQL password
2. Try common passwords (I can test them)
3. Set a new password via Herd
4. Install standalone MySQL instead
