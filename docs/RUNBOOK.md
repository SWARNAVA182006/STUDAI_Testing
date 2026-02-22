# StudAI Career - Operations Runbook

> **Operations Manual for Production Systems**
> Last Updated: February 2026

---

## Table of Contents

1. [Quick Reference Commands](#1-quick-reference-commands)
2. [Health Checks](#2-health-checks)
3. [Queue Operations](#3-queue-operations)
4. [AI Service Operations](#4-ai-service-operations)
5. [Payment Operations](#5-payment-operations)
6. [Agent Operations](#6-agent-operations)
7. [Database Operations](#7-database-operations)
8. [Cache Operations](#8-cache-operations)
9. [Incident Response](#9-incident-response)
10. [Scheduled Tasks](#10-scheduled-tasks)
11. [Monitoring & Alerting](#11-monitoring--alerting)
12. [Deployment Checklist](#12-deployment-checklist)

---

## 1. Quick Reference Commands

### Application Status
```bash
# Check application health
curl http://localhost:8000/health

# Check readiness (DB, Redis, Queue)
curl http://localhost:8000/ready

# View application logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize
```

### Queue Management
```bash
# Start queue worker
php artisan queue:work --queue=high,default,low,ai,search

# Start Horizon (recommended for production)
php artisan horizon

# View Horizon dashboard
# Navigate to /horizon in browser (admin only)

# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job_id}

# Retry all failed jobs
php artisan queue:retry all
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status

# Seed demo data (development only)
php artisan db:seed
```

---

## 2. Health Checks

### Endpoints

| Endpoint | Purpose | Expected Response |
|----------|---------|-------------------|
| `GET /health` | Basic health check | `{"status": "ok"}` |
| `GET /ready` | Readiness with dependencies | `{"status": "ready", "checks": {...}}` |

### Manual Verification
```bash
# Database connectivity
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"

# Redis connectivity
php artisan tinker --execute="Redis::ping(); echo 'Redis OK';"

# Queue connectivity
php artisan queue:monitor high,default,low

# Meilisearch connectivity
curl http://localhost:7700/health
```

### Critical Checks
1. **MySQL** - Primary data store
2. **Redis** - Cache, sessions, queues
3. **Meilisearch** - Job search functionality
4. **Azure OpenAI** - AI features
5. **Payment Gateways** - Razorpay/PayU

---

## 3. Queue Operations

### Queue Priorities
```
high      → Payments, auth, critical notifications
default   → Standard operations
low       → Background analytics
ai        → AI-intensive operations (GPT calls)
search    → Indexing, embeddings
predictions → SCOUT predictive analytics
pipeline-updates → Talent pipeline updates
candidate-discovery → Passive candidate discovery
```

### Common Operations

#### Start Workers
```bash
# Single worker (development)
php artisan queue:work --queue=high,default,low,ai,search

# Horizon (production recommended)
php artisan horizon

# Specific queue only
php artisan queue:work --queue=high
```

#### Monitor Queue Health
```bash
# Check queue sizes
php artisan tinker --execute="
    \$queues = ['high', 'default', 'low', 'ai', 'search'];
    foreach (\$queues as \$q) {
        echo \$q . ': ' . Queue::size(\$q) . PHP_EOL;
    }
"

# View pending jobs
php artisan horizon:status
```

#### Handle Failed Jobs
```bash
# List failed jobs
php artisan queue:failed

# View specific failed job
php artisan queue:failed {job_id}

# Retry specific job
php artisan queue:retry {job_id}

# Retry all failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush
```

#### Emergency: Stop All Queues
```bash
# Immediate stop
php artisan queue:restart

# If using Horizon
php artisan horizon:terminate
```

---

## 4. AI Service Operations

### Circuit Breaker Status
```bash
# Check circuit breaker state
php artisan tinker --execute="
    \$cb = app(\App\Services\CircuitBreakerService::class);
    echo 'Azure OpenAI: ' . \$cb->getState('azure_openai') . PHP_EOL;
"

# Reset circuit breaker
php artisan tinker --execute="
    \$cb = app(\App\Services\CircuitBreakerService::class);
    \$cb->reset('azure_openai');
    echo 'Circuit breaker reset';
"
```

### AI Service Health
```bash
# Test AI connectivity
php artisan tinker --execute="
    \$ai = app(\App\Services\AI\AIService::class);
    echo \$ai->isAvailable() ? 'AI Service OK' : 'AI Service DOWN';
"

# Check token usage
php artisan tinker --execute="
    \$ai = app(\App\Services\AI\AIService::class);
    print_r(\$ai->getUsageStats());
"
```

### When AI is Down
1. Circuit breaker will open after 5 consecutive failures
2. Requests will fail fast for 30 seconds
3. After half-open state, one request is allowed through
4. If successful, circuit closes; if failed, remains open

**Manual Fallback:**
```bash
# Force fallback mode for AI features
php artisan tinker --execute="
    Cache::put('ai_fallback_mode', true, 3600);
"

# Disable fallback mode
php artisan tinker --execute="
    Cache::forget('ai_fallback_mode');
"
```

---

## 5. Payment Operations

### Common Tasks

#### Check Payment Status
```bash
# Find transaction
php artisan tinker --execute="
    \$tx = \App\Models\PaymentTransaction::where('gateway_transaction_id', 'pay_xxx')->first();
    print_r(\$tx?->toArray());
"
```

#### Verify Webhook Processing
```bash
# Check recent webhooks
tail -f storage/logs/laravel.log | grep -i "webhook\|razorpay\|payu"
```

#### Manual Payment Verification
```bash
# Verify Razorpay payment
php artisan tinker --execute="
    \$api = new \Razorpay\Api\Api(config('payment.razorpay.key_id'), config('payment.razorpay.key_secret'));
    \$payment = \$api->payment->fetch('pay_xxx');
    print_r(\$payment);
"
```

### Grace Period Management

#### Check Past-Due Subscriptions
```bash
php artisan tinker --execute="
    \$pastDue = \App\Models\UserSubscription::where('status', 'past_due')
        ->with('user')
        ->get(['id', 'user_id', 'status', 'grace_period_ends_at', 'failure_count']);
    foreach (\$pastDue as \$sub) {
        echo \$sub->id . ': ' . \$sub->user->email . ' - Ends: ' . \$sub->grace_period_ends_at . PHP_EOL;
    }
"
```

#### Force Payment Retry
```bash
php artisan tinker --execute="
    \App\Jobs\RetryFailedPaymentJob::dispatch(\$subscriptionId, 1);
"
```

#### Manually Extend Grace Period
```bash
php artisan tinker --execute="
    \$sub = \App\Models\UserSubscription::find(\$id);
    \$sub->update(['grace_period_ends_at' => now()->addDays(3)]);
"
```

---

## 6. Agent Operations

### Monitor Active Agents
```bash
php artisan tinker --execute="
    \$agents = \App\Models\AgentConfiguration::where('is_active', true)
        ->with('user')
        ->get(['id', 'user_id', 'is_active', 'status']);
    foreach (\$agents as \$a) {
        echo \$a->id . ': ' . \$a->user->email . ' - ' . \$a->status . PHP_EOL;
    }
"
```

### Emergency Stop All Agents
```bash
# Stop all agents immediately
php artisan tinker --execute="
    \App\Models\AgentConfiguration::where('is_active', true)
        ->update([
            'is_active' => false,
            'emergency_stopped_at' => now(),
            'emergency_stop_reason' => 'Manual emergency stop',
        ]);
    echo 'All agents stopped';
"
```

### Stop Specific Agent
```bash
php artisan tinker --execute="
    \$agent = \App\Models\AgentConfiguration::find(\$agentId);
    \$agent->emergencyStop(\$userId, 'Manual stop reason');
"
```

### Resume Agents
```bash
php artisan tinker --execute="
    \App\Models\AgentConfiguration::whereNotNull('emergency_stopped_at')
        ->update([
            'is_active' => true,
            'emergency_stopped_at' => null,
            'emergency_stopped_by' => null,
            'emergency_stop_reason' => null,
        ]);
    echo 'Agents resumed';
"
```

### Check Agent Performance
```bash
php artisan tinker --execute="
    \$metrics = \App\Models\AgentLearningMetric::where('agent_configuration_id', \$agentId)
        ->latest()
        ->first();
    print_r(\$metrics?->toArray());
"
```

---

## 7. Database Operations

### Connection Check
```bash
php artisan tinker --execute="
    try {
        DB::connection()->getPdo();
        echo 'Database connected: ' . DB::connection()->getDatabaseName();
    } catch (\Exception \$e) {
        echo 'Database connection failed: ' . \$e->getMessage();
    }
"
```

### Common Queries

#### Active Users in Last 24h
```bash
php artisan tinker --execute="
    echo \App\Models\User::where('last_login_at', '>', now()->subDay())->count();
"
```

#### Applications Today
```bash
php artisan tinker --execute="
    echo \App\Models\Application::whereDate('created_at', today())->count();
"
```

#### Jobs Posted This Week
```bash
php artisan tinker --execute="
    echo \App\Models\Job::where('created_at', '>', now()->subWeek())->count();
"
```

### Backup Operations
```bash
# Backup database (example with mysqldump)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > backup_$(date +%Y%m%d).sql

# Verify backup integrity
mysql -u $DB_USER -p$DB_PASS $DB_NAME < backup.sql --execute="SELECT 1;"
```

---

## 8. Cache Operations

### Clear Caches
```bash
# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear      # Application cache
php artisan config:clear     # Config cache
php artisan route:clear      # Route cache
php artisan view:clear       # Compiled views
```

### Rebuild Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Check Cache Status
```bash
# Redis stats
redis-cli info stats

# Check specific cache key
php artisan tinker --execute="
    \$value = Cache::get('your_key');
    var_dump(\$value);
"
```

### Flush Rate Limits
```bash
php artisan tinker --execute="
    RateLimiter::clear('api');
    echo 'Rate limits cleared';
"
```

---

## 9. Incident Response

### Severity Levels

| Level | Description | Response Time |
|-------|-------------|---------------|
| P1 | Complete outage, payments down | Immediate |
| P2 | Major feature broken, significant user impact | 15 minutes |
| P3 | Minor feature broken, workaround available | 1 hour |
| P4 | Low impact, cosmetic issues | Next business day |

### P1: Complete Outage

1. **Check infrastructure**
   ```bash
   curl http://localhost:8000/health
   curl http://localhost:8000/ready
   ```

2. **Check logs for errors**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

3. **Check database**
   ```bash
   php artisan tinker --execute="DB::connection()->getPdo();"
   ```

4. **Check Redis**
   ```bash
   redis-cli ping
   ```

5. **Restart services if needed**
   ```bash
   php artisan optimize:clear
   php artisan queue:restart
   ```

### P1: Payment System Down

1. **Check payment gateway status**
   - Razorpay: https://status.razorpay.com/
   - PayU: Check provider status page

2. **Check webhook logs**
   ```bash
   grep -i "webhook\|payment" storage/logs/laravel.log | tail -50
   ```

3. **Verify idempotency keys**
   ```bash
   php artisan tinker --execute="
       \App\Models\IdempotencyKey::where('created_at', '>', now()->subHour())->count();
   "
   ```

4. **If gateway is down, enable maintenance mode for payments**
   ```bash
   php artisan tinker --execute="
       Cache::put('payment_maintenance', true, 3600);
   "
   ```

### AI Service Degradation

1. **Check circuit breaker status**
   ```bash
   php artisan tinker --execute="
       \$cb = app(\App\Services\CircuitBreakerService::class);
       echo \$cb->getState('azure_openai');
   "
   ```

2. **If circuit is open, check Azure status**
   - Azure Status: https://status.azure.com/

3. **Enable graceful degradation**
   ```bash
   php artisan tinker --execute="
       Cache::put('ai_fallback_mode', true, 3600);
   "
   ```

4. **Reset circuit breaker after recovery**
   ```bash
   php artisan tinker --execute="
       \$cb = app(\App\Services\CircuitBreakerService::class);
       \$cb->reset('azure_openai');
   "
   ```

---

## 10. Scheduled Tasks

### List All Scheduled Tasks
```bash
php artisan schedule:list
```

### Key Schedules

| Task | Frequency | Description |
|------|-----------|-------------|
| Job Alerts | Daily 9 AM | Send job alert emails |
| Job Discovery | Hourly | Agent job discovery |
| Submit Applications | Every 15 min | Process auto applications |
| Market Data Update | Hourly | Update market intelligence |
| Skill Gap Analysis | Daily 2 AM | Analyze user skill gaps |
| Update Predictions | Daily 4 AM | SCOUT predictive analytics |
| Talent Pipeline Update | Daily 2 AM | Pipeline health scoring |
| Payment Retry | Every 6 hours | Retry failed payments |
| Grace Period Check | Hourly | Process expired grace periods |
| Failed Jobs Check | Hourly | Alert on failed jobs |
| Idempotency Cleanup | Daily 5 AM | Clean expired keys |

### Manually Run Scheduled Task
```bash
# Run all due tasks
php artisan schedule:run

# Test specific scheduled command
php artisan jobs:send-alerts
```

### Check Scheduler Health
```bash
# Verify scheduler is running
tail -f storage/logs/laravel.log | grep "schedule"

# Check cron is configured
crontab -l | grep artisan
```

### Expected Cron Entry
```
* * * * * cd /path/to/studai-career && php artisan schedule:run >> /dev/null 2>&1
```

---

## 11. Monitoring & Alerting

### Horizon Dashboard
- URL: `/horizon`
- Access: Admin users only
- Shows: Queue metrics, failed jobs, recent jobs

### Key Metrics to Monitor

| Metric | Warning Threshold | Critical Threshold |
|--------|-------------------|-------------------|
| Queue size (high) | > 100 | > 500 |
| Queue size (default) | > 500 | > 2000 |
| Failed jobs/hour | > 5 | > 20 |
| Response time (avg) | > 500ms | > 2000ms |
| Error rate | > 1% | > 5% |
| AI latency | > 3s | > 10s |

### Log Monitoring
```bash
# Watch for errors
tail -f storage/logs/laravel.log | grep -i "error\|exception\|critical"

# Watch payment activity
tail -f storage/logs/laravel.log | grep -i "payment\|razorpay\|payu"

# Watch agent activity
tail -f storage/logs/laravel.log | grep -i "agent\|auto.application"

# Watch AI activity
tail -f storage/logs/laravel.log | grep -i "openai\|ai.service\|circuit"
```

### Alerting Checklist
- [ ] Sentry configured for exception tracking
- [ ] Email alerts for failed jobs exceeding threshold
- [ ] Slack/Teams webhook for critical errors
- [ ] Uptime monitoring for /health endpoint
- [ ] Database connection monitoring
- [ ] Redis memory monitoring
- [ ] Disk space monitoring for logs

---

## 12. Deployment Checklist

### Pre-Deployment
- [ ] Run tests: `php artisan test`
- [ ] Check for migration changes
- [ ] Review environment variables
- [ ] Backup database

### Deployment Steps
```bash
# 1. Enable maintenance mode
php artisan down --render="errors::503"

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan optimize:clear
php artisan optimize
php artisan view:cache

# 6. Restart queue workers
php artisan queue:restart

# 7. If using Horizon
php artisan horizon:terminate
# (Supervisor will restart Horizon)

# 8. Disable maintenance mode
php artisan up
```

### Post-Deployment Verification
- [ ] `/health` returns 200
- [ ] `/ready` returns ready status
- [ ] Sample API request succeeds
- [ ] Admin panel loads
- [ ] Queue workers processing jobs
- [ ] No new errors in logs

### Rollback Procedure
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Rollback code
git checkout {previous_tag}

# 3. Rollback migration if needed
php artisan migrate:rollback

# 4. Clear caches
php artisan optimize:clear

# 5. Restart workers
php artisan queue:restart

# 6. Disable maintenance mode
php artisan up
```

---

## Emergency Contacts

| Role | Contact |
|------|---------|
| DevOps Lead | [Contact info] |
| Backend Lead | [Contact info] |
| Database Admin | [Contact info] |
| Security Team | [Contact info] |

---

## Quick Troubleshooting

| Symptom | Check | Action |
|---------|-------|--------|
| 500 errors | `storage/logs/laravel.log` | Fix error, clear cache |
| Queue not processing | `php artisan queue:work` | Restart workers |
| AI not responding | Circuit breaker status | Reset circuit or enable fallback |
| Payment failures | Gateway status page | Check webhook logs |
| Slow responses | Database connections | Scale or optimize queries |
| High memory | Redis memory | Clear cache or scale Redis |
| Jobs stuck | Horizon dashboard | Restart specific queue |

---

*This runbook should be kept up-to-date with any infrastructure or operational changes.*
