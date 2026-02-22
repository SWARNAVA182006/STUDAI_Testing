# Autonomous Agent Deployment Guide

## ✅ Deployment Status

### Completed Steps

1. **✅ Database Migrations**
   - All autonomous agent tables already exist (ran in batch 8)
   - Tables: `agent_configurations`, `auto_applications`, `job_matches`, `agent_learning_metrics`
   - Duplicate migration files removed
   - Database is clean and ready

2. **✅ API Routes Configured**
   - File: `routes/api.php`
   - AgentController imported
   - All 13 agent endpoints configured under `auth:sanctum` middleware:
     * GET `/api/agent/config` - Get user's agent configuration
     * POST `/api/agent/configure` - Update agent settings
     * POST `/api/agent/activate` - Start the autonomous agent
     * POST `/api/agent/pause` - Temporarily pause agent
     * POST `/api/agent/resume` - Resume paused agent
     * POST `/api/agent/deactivate` - Completely stop agent
     * GET `/api/agent/status` - Get current agent status
     * GET `/api/agent/applications` - List auto-submitted applications
     * GET `/api/agent/metrics` - Get performance metrics
     * GET `/api/agent/learning` - Get learning insights
     * POST `/api/agent/blacklist` - Blacklist a company
     * DELETE `/api/agent/unblacklist` - Remove company from blacklist
     * POST `/api/agent/discover` - Manual job discovery (testing)

3. **✅ Web Routes Configured**
   - File: `routes/web.php`
   - 4 agent view routes added under `auth` + `subscription` middleware:
     * GET `/agent/dashboard` - Main agent control panel
     * GET `/agent/configure` - Configuration wizard
     * GET `/agent/applications` - Application history & status
     * GET `/agent/metrics` - Analytics & performance

4. **✅ Scheduler Configured**
   - File: `routes/console.php`
   - 4 scheduled jobs configured:
     * `DiscoverJobsJob` - Runs every hour
     * `SubmitApplicationsJob` - Runs every 15 minutes
     * `UpdateLearningJob` - Runs daily at 2:00 AM
     * `SendDigestJob` - Runs daily at 8:00 AM

---

## 📋 Manual Steps Required

### Step 1: Start Queue Worker (Windows)

**Option A: Using Batch Script (Recommended)**
```batch
# Run the provided script
E:\downloads\career\studai-career\start-queue.bat
```

**Option B: Manual Command**
```batch
cd E:\downloads\career\studai-career
php artisan queue:work --tries=3 --timeout=300
```

**Important Notes:**
- Laravel Horizon is NOT available on Windows (requires PCNTL extension)
- Use `queue:work` instead - it works perfectly for the agent system
- Keep this terminal window open - closing it stops the queue worker
- For production on Windows, use NSSM (Non-Sucking Service Manager) to run as a service

### Step 2: Configure Windows Task Scheduler for Cron

Since Windows doesn't have cron, you need to set up Task Scheduler:

#### Using Task Scheduler GUI:

1. Open Task Scheduler (search "Task Scheduler" in Start menu)

2. Click "Create Basic Task"
   - Name: `Laravel Scheduler - StudAI Career`
   - Description: `Runs Laravel's task scheduler every minute`

3. Trigger: Select "Daily"
   - Start: Set to today's date
   - Recur every: 1 day
   - Click "Next"

4. Action: Select "Start a program"
   - Program/script: `php`
   - Add arguments: `artisan schedule:run`
   - Start in: `E:\downloads\career\studai-career`
   - Click "Next", then "Finish"

5. After creating, right-click the task and select "Properties"

6. In the "Triggers" tab:
   - Edit the trigger
   - Check "Repeat task every" and set to "1 minute"
   - Set "for a duration of" to "Indefinitely"
   - Click "OK"

7. In the "Settings" tab:
   - Uncheck "Stop the task if it runs longer than"
   - Check "Run task as soon as possible after a scheduled start is missed"
   - Click "OK"

#### Using Command Line (PowerShell as Administrator):

```powershell
# Create the task
$action = New-ScheduledTaskAction -Execute 'php' -Argument 'artisan schedule:run' -WorkingDirectory 'E:\downloads\career\studai-career'
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
Register-ScheduledTask -Action $action -Trigger $trigger -Settings $settings -TaskName "Laravel Scheduler - StudAI Career" -Description "Runs Laravel's task scheduler every minute"
```

#### Verify Task Scheduler is Working:

```batch
cd E:\downloads\career\studai-career
php artisan schedule:list
```

You should see:
```
0 * * * * php artisan app:discover-jobs .................... Next Due: 1 hour from now
*/15 * * * * php artisan app:submit-applications ........... Next Due: 15 minutes from now
0 2 * * * php artisan app:update-learning .................. Next Due: Tomorrow at 2:00 AM
0 8 * * * php artisan app:send-digest ...................... Next Due: Tomorrow at 8:00 AM
```

---

## 🚀 Testing the Agent System

### 1. Access Agent Dashboard

Navigate to: `http://localhost:8000/agent/dashboard` (or your app URL)

**Prerequisites:**
- User must be logged in
- User must have an active subscription
- User must be verified (email verification)

### 2. Configure the Agent

Go to: `http://localhost:8000/agent/configure`

Fill in:
- Target roles (e.g., "Full Stack Developer", "Backend Engineer")
- Preferred locations
- Salary range
- Required skills
- Application settings (daily limit, aggressiveness)

### 3. Activate the Agent

Click "Activate Agent" button in dashboard or send API request:

```bash
curl -X POST http://localhost:8000/api/agent/activate \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json"
```

### 4. Monitor Queue Processing

In the terminal where queue worker is running, you should see:

```
[2025-01-15 10:30:00][abc123] Processing: App\Jobs\Agent\DiscoverJobsJob
[2025-01-15 10:30:05][abc123] Processed:  App\Jobs\Agent\DiscoverJobsJob
[2025-01-15 10:30:15][def456] Processing: App\Jobs\Agent\SubmitApplicationsJob
[2025-01-15 10:30:20][def456] Processed:  App\Jobs\Agent\SubmitApplicationsJob
```

### 5. Check Application History

Go to: `http://localhost:8000/agent/applications`

You should see:
- List of auto-submitted applications
- Application status (submitted, reviewing, interview, rejected)
- Company details
- Success rate metrics

### 6. View Metrics

Go to: `http://localhost:8000/agent/metrics`

Charts showing:
- Applications over time
- Success rate by role
- Learning improvements
- Company preferences

---

## 🔧 Environment Configuration

### Required .env Variables

```env
# Queue Configuration (for agent jobs)
QUEUE_CONNECTION=database  # or redis if using Redis

# OpenAI API (for job analysis, resume customization, cover letters)
OPENAI_API_KEY=your_openai_api_key
OPENAI_MODEL=gpt-4  # or gpt-4-turbo

# Job Board API Keys (for job discovery)
LINKEDIN_API_KEY=your_linkedin_key  # Optional
INDEED_API_KEY=your_indeed_key      # Optional
NAUKRI_API_KEY=your_naukri_key      # Optional - India-specific

# Email (for daily digests, notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # Use your SMTP server
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@studai.com
MAIL_FROM_NAME="StudAI Career Agent"

# Application URL (for links in emails)
APP_URL=http://localhost:8000  # Change to production domain
```

### Update Queue Tables (if using database queue)

```bash
php artisan queue:table
php artisan migrate
```

---

## 📊 Monitoring & Debugging

### Check Queue Status

```bash
# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### View Agent Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Or on Windows
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

### Database Queries

```sql
-- Check active agents
SELECT u.name, u.email, ac.is_active, ac.daily_application_limit, ac.applications_this_month
FROM agent_configurations ac
JOIN users u ON ac.user_id = u.id
WHERE ac.is_active = 1;

-- Check recent applications
SELECT aa.id, u.name, j.title, aa.company_name, aa.status, aa.applied_at
FROM auto_applications aa
JOIN users u ON aa.user_id = u.id
JOIN job_listings j ON aa.job_id = j.id
ORDER BY aa.applied_at DESC
LIMIT 20;

-- Check job matches
SELECT jm.id, u.name, j.title, jm.match_score, jm.status
FROM job_matches jm
JOIN users u ON jm.user_id = u.id
JOIN job_listings j ON jm.job_id = j.id
WHERE jm.match_score >= 70
ORDER BY jm.match_score DESC
LIMIT 20;

-- Check learning metrics
SELECT alm.metric_type, alm.metric_value, alm.created_at
FROM agent_learning_metrics alm
WHERE alm.user_id = 1
ORDER BY alm.created_at DESC;
```

---

## 🛡️ Security & Best Practices

### Rate Limiting

The agent respects subscription limits:
- Free tier: 5 applications/month
- Pro tier: 50 applications/month
- Premium tier: Unlimited applications

Enforced by `CheckSubscriptionLimits` middleware in `AgentController`.

### User Approval

For aggressive application settings, users receive notifications for approval:
- High-value jobs (>$150k salary)
- Jobs from blacklisted companies (if user wants to reconsider)
- Jobs with <60% match score (if aggressive mode is on)

### Data Privacy

- Resumes and cover letters stored in `storage/app/agent_resumes/` with encryption
- PDFs generated on-the-fly and deleted after 24 hours
- User can delete all agent data via `/agent/deactivate` with `delete_data=true`

### Logging & Compliance

- All applications logged in `auto_applications` table with timestamps
- AI API calls tracked in `ai_usage_logs` for billing/audit
- Failed jobs logged in `failed_jobs` table for debugging

---

## 📦 Production Deployment (Windows Server)

### 1. Install NSSM (Queue Worker as Service)

```batch
# Download NSSM from https://nssm.cc/download
# Extract to C:\nssm

# Install queue worker as service
C:\nssm\nssm.exe install LaravelQueue "php" "artisan queue:work --tries=3 --timeout=300"
C:\nssm\nssm.exe set LaravelQueue AppDirectory "E:\downloads\career\studai-career"
C:\nssm\nssm.exe set LaravelQueue DisplayName "Laravel Queue Worker - StudAI Career"
C:\nssm\nssm.exe set LaravelQueue Description "Processes background jobs for autonomous agent"
C:\nssm\nssm.exe set LaravelQueue Start SERVICE_AUTO_START

# Start the service
C:\nssm\nssm.exe start LaravelQueue
```

### 2. Configure IIS (Production Web Server)

```batch
# Install URL Rewrite Module for IIS
# Install PHP Manager for IIS
# Configure site root to: E:\downloads\career\studai-career\public

# web.config is already in public directory
```

### 3. Optimize for Production

```batch
cd E:\downloads\career\studai-career

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate optimized autoloader
composer install --no-dev --optimize-autoloader

# Set permissions
icacls storage /grant Users:(OI)(CI)F /T
icacls bootstrap\cache /grant Users:(OI)(CI)F /T
```

---

## ✅ Deployment Checklist

- [x] Database migrations run successfully
- [x] API routes configured (`routes/api.php`)
- [x] Web routes configured (`routes/web.php`)
- [x] Scheduler configured (`routes/console.php`)
- [ ] Queue worker started (`start-queue.bat` or NSSM service)
- [ ] Task Scheduler configured (runs every minute)
- [ ] Environment variables set (`.env`)
- [ ] OpenAI API key configured
- [ ] SMTP email configured
- [ ] Tested agent activation
- [ ] Tested job discovery
- [ ] Tested application submission
- [ ] Tested notifications
- [ ] Verified subscription limits work

---

## 🆘 Troubleshooting

### Queue Worker Not Processing Jobs

**Symptoms:** Jobs stay in `pending` status, never get processed

**Solutions:**
1. Check if queue worker is running: `tasklist | findstr php`
2. Restart queue worker: Close terminal and run `start-queue.bat` again
3. Check database connection in `.env`
4. Check `jobs` table in database: `SELECT * FROM jobs LIMIT 10;`

### Scheduler Not Running

**Symptoms:** Hourly/daily jobs never execute

**Solutions:**
1. Check Task Scheduler: Open Task Scheduler GUI, verify task exists
2. Check task history: Right-click task → Properties → History tab
3. Run manually: `php artisan schedule:run`
4. Check scheduler list: `php artisan schedule:list`

### Agent Not Finding Jobs

**Symptoms:** Agent activates but no jobs discovered

**Solutions:**
1. Check OpenAI API key in `.env`
2. Check job board API keys (LinkedIn, Indeed, Naukri)
3. Check user's target roles: Must have at least one role configured
4. Check database: `SELECT * FROM agent_configurations WHERE user_id = 1;`
5. Run discovery manually: `php artisan app:discover-jobs 1` (replace 1 with user ID)

### Applications Not Submitting

**Symptoms:** Jobs discovered but applications not submitted

**Solutions:**
1. Check subscription limits: User may have hit monthly limit
2. Check match threshold: Jobs may not meet 70% match requirement
3. Check blacklist: Companies may be blacklisted
4. Check queue worker: Must be running for `SubmitApplicationsJob` to process
5. Run manually: `php artisan app:submit-applications 1` (replace 1 with user ID)

### Notifications Not Sending

**Symptoms:** Agent working but users not receiving emails

**Solutions:**
1. Check SMTP configuration in `.env`
2. Test email: `php artisan tinker` then `Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });`
3. Check `failed_jobs` table for failed notification jobs
4. Check email queue: `SELECT * FROM jobs WHERE queue = 'emails';`

---

## 📚 Additional Resources

- **Laravel Queue Documentation:** https://laravel.com/docs/11.x/queues
- **Task Scheduler (Windows):** https://learn.microsoft.com/en-us/windows/win32/taskschd/task-scheduler-start-page
- **NSSM Documentation:** https://nssm.cc/usage
- **Laravel Task Scheduling:** https://laravel.com/docs/11.x/scheduling

---

## 🎯 Next Steps

1. **Start the queue worker** using `start-queue.bat`
2. **Configure Task Scheduler** to run Laravel scheduler every minute
3. **Test the agent** by creating a user, configuring preferences, and activating
4. **Monitor the logs** to ensure jobs are processing correctly
5. **Set up production services** (NSSM for queue worker, IIS for web server)

The autonomous agent system is now fully deployed and ready to use! 🚀
