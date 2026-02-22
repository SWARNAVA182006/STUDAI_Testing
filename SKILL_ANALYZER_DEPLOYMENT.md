# Skill Gap Analyzer - Deployment Guide

This guide covers deploying the **Intelligent Skill Gap Analyzer with Auto-Learning Recommendations** feature to production.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Configuration](#environment-configuration)
3. [Database Migration](#database-migration)
4. [Queue Worker Setup](#queue-worker-setup)
5. [Task Scheduler Activation](#task-scheduler-activation)
6. [Service Dependencies](#service-dependencies)
7. [Feature Flags](#feature-flags)
8. [Testing Checklist](#testing-checklist)
9. [Performance Optimization](#performance-optimization)
10. [Monitoring & Logging](#monitoring--logging)
11. [Rollback Plan](#rollback-plan)

---

## Prerequisites

### System Requirements
- **PHP**: 8.2 or higher
- **Laravel**: 11.x
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache/Queue**: Redis 6.0+
- **Node.js**: 18.x+ (for asset compilation)
- **Supervisor**: For queue worker management

### Required Extensions
```bash
# Check PHP extensions
php -m | grep -E "openssl|pdo|mbstring|tokenizer|xml|ctype|json|bcmath|redis|curl"
```

---

## Environment Configuration

### 1. OpenAI API Configuration

Add to `.env`:

```env
# OpenAI API (required for AI services)
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4
OPENAI_MAX_TOKENS=2000
OPENAI_TEMPERATURE=0.7

# AI Usage Tracking
AI_USAGE_LOG_ENABLED=true
AI_COST_TRACKING_ENABLED=true
```

**Cost Estimation**:
- Skill gap analysis: ~$0.02-0.05 per user
- Learning path curation: ~$0.10-0.30 per path
- Daily recommendations: ~$0.01 per user
- Market trends update: ~$5-10 per week

**Recommended monthly budget**: $100-300 for 1,000 active users

### 2. YouTube Data API (Optional)

For enhanced learning resource discovery:

```env
# YouTube Data API v3
YOUTUBE_API_KEY=AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxx
YOUTUBE_QUOTA_LIMIT=10000
```

Get API key from: https://console.cloud.google.com/apis/credentials

### 3. Queue Configuration

```env
# Queue Driver (Redis recommended for production)
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Queue Timeouts
QUEUE_TIMEOUT=600
QUEUE_RETRY_AFTER=900
```

### 4. Mail Configuration

For daily learning recommendations and notifications:

```env
# Mail Settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@studai-career.com
MAIL_FROM_NAME="${APP_NAME}"

# Admin Email (for job failure notifications)
ADMIN_EMAIL=admin@studai-career.com
```

### 5. Cache Configuration

```env
# Cache Driver (Redis recommended)
CACHE_DRIVER=redis
CACHE_PREFIX=studai_career

# AI Response Cache TTL (in seconds)
AI_CACHE_TTL=3600
RESOURCE_CACHE_TTL=86400
TREND_CACHE_TTL=604800
```

---

## Database Migration

### 1. Run Migrations

```bash
# Backup database first!
php artisan db:backup

# Run skill analyzer migrations
php artisan migrate

# Verify tables created
php artisan db:table skill_gaps
php artisan db:table skill_validations
php artisan db:table learning_paths
php artisan db:table learning_resources
php artisan db:table learning_progress
php artisan db:table skill_assessments
php artisan db:table ai_usage_logs
```

### 2. Seed Test Data (Staging Only)

```bash
# DO NOT run in production!
php artisan db:seed --class=SkillAnalyzerSeeder
```

### 3. Database Indexes

Ensure these indexes exist for optimal performance:

```sql
-- Skill Gaps
CREATE INDEX idx_skill_gaps_user_severity ON skill_gaps(user_id, gap_severity);
CREATE INDEX idx_skill_gaps_trend ON skill_gaps(trend_direction, trend_score);
CREATE INDEX idx_skill_gaps_priority ON skill_gaps(priority_score DESC);

-- Learning Paths
CREATE INDEX idx_learning_paths_status ON learning_paths(status, user_id);
CREATE INDEX idx_learning_paths_completion ON learning_paths(completion_percentage);

-- Learning Resources
CREATE INDEX idx_learning_resources_relevance ON learning_resources(relevance_score DESC);
CREATE INDEX idx_learning_resources_stale ON learning_resources(is_stale);

-- Assessments
CREATE INDEX idx_assessments_status ON skill_assessments(status, user_id);
CREATE INDEX idx_assessments_hash ON skill_assessments(certificate_hash);

-- AI Usage Logs (for analytics)
CREATE INDEX idx_ai_usage_date ON ai_usage_logs(created_at, service_name);
```

---

## Queue Worker Setup

### 1. Supervisor Configuration

Create `/etc/supervisor/conf.d/studai-skill-analyzer.conf`:

```ini
[program:studai-skill-analyzer-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/studai-career/artisan queue:work redis --queue=skill-analysis,email,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/studai-career/storage/logs/skill-analyzer-worker.log
stopwaitsecs=3600
```

**Key Settings**:
- `numprocs=4`: Run 4 parallel workers (adjust based on server capacity)
- `--queue=skill-analysis,email,default`: Process queues in priority order
- `--max-time=3600`: Restart worker after 1 hour to prevent memory leaks

### 2. Start Supervisor

```bash
# Reload configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start studai-skill-analyzer-worker:*

# Check status
sudo supervisorctl status
```

### 3. Queue Monitoring

```bash
# Monitor queue in real-time
php artisan queue:monitor redis:skill-analysis,redis:email --max=100

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Task Scheduler Activation

### 1. Cron Entry

Add to crontab (`crontab -e`):

```cron
* * * * * cd /var/www/studai-career && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Verify Scheduled Tasks

```bash
# List all scheduled tasks
php artisan schedule:list

# Test schedule without running
php artisan schedule:test

# Run schedule manually
php artisan schedule:run
```

### 3. Scheduled Jobs Overview

| Job | Schedule | Queue | Description |
|-----|----------|-------|-------------|
| `AnalyzeSkillGapsJob` | Daily 2:00 AM | skill-analysis | Analyze gaps for all users |
| `CurateLearningResourcesJob` | Weekly Sun 3:00 AM | default | Update resource ratings |
| `SendDailyLearningRecommendationJob` | Daily 8:00 AM | email | Send personalized emails |
| `UpdateMarketTrendsJob` | Weekly Mon 1:00 AM | default | Refresh industry trends |
| `ValidateUserSkillsJob` | On-demand | skill-analysis | Validate work history skills |

---

## Service Dependencies

### 1. OpenAI Account Setup

1. Create account at https://platform.openai.com/
2. Add payment method (required for GPT-4 access)
3. Generate API key
4. Set usage limits: **Soft limit $100/month, Hard limit $150/month**
5. Enable usage notifications

### 2. YouTube Data API Setup

1. Create Google Cloud project
2. Enable YouTube Data API v3
3. Create credentials (API key)
4. Set quota limit: 10,000 units/day (free tier)
5. Monitor usage in GCP Console

### 3. Email Service

Recommended providers:
- **Mailgun**: 5,000 emails/month free
- **SendGrid**: 100 emails/day free
- **Amazon SES**: $0.10 per 1,000 emails

### 4. Redis Setup

```bash
# Install Redis
sudo apt-get install redis-server

# Configure persistence
sudo nano /etc/redis/redis.conf
# Set: save 900 1, save 300 10, save 60 10000

# Start Redis
sudo systemctl start redis
sudo systemctl enable redis

# Test connection
redis-cli ping
```

---

## Feature Flags

### 1. Enable Skill Analyzer

Add to `config/features.php`:

```php
return [
    'skill_analyzer' => [
        'enabled' => env('SKILL_ANALYZER_ENABLED', true),
        'ai_services' => [
            'gap_analysis' => env('SKILL_ANALYZER_GAP_ANALYSIS', true),
            'path_curation' => env('SKILL_ANALYZER_PATH_CURATION', true),
            'skill_validation' => env('SKILL_ANALYZER_VALIDATION', true),
            'assessments' => env('SKILL_ANALYZER_ASSESSMENTS', true),
            'trend_prediction' => env('SKILL_ANALYZER_TRENDS', true),
        ],
        'features' => [
            'daily_emails' => env('SKILL_ANALYZER_DAILY_EMAILS', true),
            'certificates' => env('SKILL_ANALYZER_CERTIFICATES', true),
            'youtube_resources' => env('SKILL_ANALYZER_YOUTUBE', true),
        ],
    ],
];
```

### 2. Gradual Rollout

Use feature flags for phased deployment:

```env
# Phase 1: Internal testing (10 users)
SKILL_ANALYZER_ENABLED=true
SKILL_ANALYZER_USER_LIMIT=10

# Phase 2: Beta (100 users)
SKILL_ANALYZER_USER_LIMIT=100

# Phase 3: Full launch
SKILL_ANALYZER_USER_LIMIT=0  # No limit
```

### 3. Check Feature Access

```php
// In controllers/middleware
if (!config('features.skill_analyzer.enabled')) {
    abort(503, 'Skill Analyzer feature temporarily unavailable');
}

// Per-user rollout
if ($user->id > config('features.skill_analyzer.user_limit', 0)) {
    return redirect()->route('coming-soon');
}
```

---

## Testing Checklist

### Pre-Deployment Tests

- [ ] Database migrations run without errors
- [ ] All 7 models accessible via Tinker
- [ ] API endpoints return expected responses
- [ ] Web routes render without 500 errors
- [ ] Queue jobs dispatch successfully
- [ ] Scheduled tasks appear in `schedule:list`
- [ ] OpenAI API connection works
- [ ] Cache writes and reads correctly
- [ ] Email notifications send
- [ ] WebSocket events broadcast (if using Reverb)

### API Endpoint Tests

```bash
# Analyze skill gaps
curl -X POST https://your-domain.com/api/skills/analyze \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"

# Generate learning path
curl -X POST https://your-domain.com/api/skills/learning-path/{gapId} \
  -H "Authorization: Bearer {token}"

# Validate skills
curl -X POST https://your-domain.com/api/skills/validate \
  -H "Authorization: Bearer {token}"

# Create assessment
curl -X POST https://your-domain.com/api/skills/assessment \
  -H "Authorization: Bearer {token}" \
  -d '{"skill_name":"Laravel","assessment_type":"mixed","difficulty":"intermediate","question_count":15}'
```

### Queue Job Tests

```bash
# Dispatch test job
php artisan tinker
>>> App\Jobs\AnalyzeSkillGapsJob::dispatch(User::first());

# Monitor processing
php artisan horizon:list

# Check logs
tail -f storage/logs/laravel.log | grep "skill-analysis"
```

### Load Testing

```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Test dashboard load
ab -n 1000 -c 10 https://your-domain.com/skills/dashboard

# Test API endpoint
ab -n 100 -c 5 -T "application/json" \
   -H "Authorization: Bearer {token}" \
   https://your-domain.com/api/skills/gaps
```

---

## Performance Optimization

### 1. Eager Loading (N+1 Prevention)

```php
// In controllers, always eager load relationships
$gaps = SkillGap::with('learningPath', 'validations')->get();
$paths = LearningPath::with('resources', 'progress', 'skillGap')->get();
```

### 2. Cache Strategies

```php
// Skill gaps cache (1 hour)
Cache::remember("skill_gaps_user_{$userId}", 3600, function () use ($userId) {
    return SkillGap::where('user_id', $userId)->get();
});

// Market trends cache (1 week)
Cache::remember('market_trends_summary', 604800, function () {
    return /* expensive calculation */;
});

// Resource embeddings cache (until resource updated)
Cache::rememberForever("resource_embedding_{$resourceId}", function () {
    return /* OpenAI embedding API call */;
});
```

### 3. Database Query Optimization

```php
// Use select() to limit columns
SkillGap::select(['id', 'skill_name', 'gap_severity', 'priority_score'])
    ->where('user_id', $userId)
    ->get();

// Use chunk() for large datasets
SkillGap::chunk(100, function ($gaps) {
    // Process in batches
});

// Use cursor() for memory efficiency
foreach (SkillGap::cursor() as $gap) {
    // Process one at a time
}
```

### 4. API Rate Limiting

Add to `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1', // 60 requests per minute
        'skill_analyzer_throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':10,1', // AI endpoints
    ],
];
```

### 5. Asset Optimization

```bash
# Compile and minify assets
npm run build

# Enable Gzip compression (Nginx)
gzip on;
gzip_types text/css application/javascript application/json;

# Browser caching (Nginx)
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 365d;
}
```

---

## Monitoring & Logging

### 1. Log Channels

Configure in `config/logging.php`:

```php
'channels' => [
    'skill_analyzer' => [
        'driver' => 'daily',
        'path' => storage_path('logs/skill-analyzer.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 14,
    ],
    
    'ai_services' => [
        'driver' => 'daily',
        'path' => storage_path('logs/ai-services.log'),
        'level' => 'debug',
        'days' => 7,
    ],
];
```

### 2. Error Tracking (Sentry)

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=https://xxxxx@sentry.io/xxxxx
```

```env
SENTRY_LARAVEL_DSN=https://xxxxx@sentry.io/xxxxx
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### 3. Performance Monitoring

Use Laravel Telescope (local/staging only):

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Production**: Use Horizon for queue monitoring:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

Access at: `https://your-domain.com/horizon`

### 4. Key Metrics to Monitor

- **Queue Length**: Should stay < 100 jobs
- **Job Processing Time**: Avg < 30s per job
- **OpenAI API Usage**: Track tokens/cost in `ai_usage_logs` table
- **Cache Hit Rate**: Should be > 80%
- **Response Time**: P95 < 500ms
- **Error Rate**: < 0.1%

---

## Rollback Plan

### 1. Database Rollback

```bash
# Rollback all skill analyzer migrations
php artisan migrate:rollback --step=1

# Or rollback to specific batch
php artisan migrate:rollback --batch=5
```

### 2. Feature Flag Disable

```env
# Quick disable without code changes
SKILL_ANALYZER_ENABLED=false
```

### 3. Queue Purging

```bash
# Clear all queued skill analyzer jobs
php artisan queue:clear redis --queue=skill-analysis

# Stop workers
sudo supervisorctl stop studai-skill-analyzer-worker:*
```

### 4. Cache Clearing

```bash
# Clear all skill analyzer caches
php artisan cache:forget skill_gaps_*
php artisan cache:forget learning_paths_*
php artisan cache:forget market_trends_*

# Or clear all cache
php artisan cache:clear
```

### 5. Full Rollback Steps

```bash
# 1. Disable feature
php artisan config:set features.skill_analyzer.enabled false

# 2. Stop scheduled tasks (comment out in routes/console.php)
# Then: php artisan config:cache

# 3. Stop queue workers
sudo supervisorctl stop studai-skill-analyzer-worker:*

# 4. Clear queues
php artisan queue:clear redis --queue=skill-analysis,email

# 5. Rollback migrations (if needed)
php artisan migrate:rollback --step=1

# 6. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Post-Deployment Checklist

- [ ] All migrations applied successfully
- [ ] Queue workers running (check `supervisorctl status`)
- [ ] Cron job active (check `crontab -l`)
- [ ] Redis connection working
- [ ] OpenAI API calls successful
- [ ] Daily emails sending at 8 AM
- [ ] Skill analysis running at 2 AM
- [ ] Resource curation running Sundays 3 AM
- [ ] Trend updates running Mondays 1 AM
- [ ] Sentry error tracking active
- [ ] Horizon dashboard accessible
- [ ] SSL certificate valid
- [ ] Backups configured
- [ ] Monitoring alerts set up

---

## Support & Troubleshooting

### Common Issues

**Issue**: Queue jobs not processing
```bash
# Check workers
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart studai-skill-analyzer-worker:*

# Check logs
tail -f storage/logs/laravel.log
```

**Issue**: OpenAI API errors
```bash
# Check API key
php artisan tinker
>>> config('services.openai.api_key')

# Test connection
>>> OpenAI::chat()->create([...])
```

**Issue**: High memory usage
```bash
# Optimize autoloader
composer dump-autoload --optimize

# Clear compiled files
php artisan clear-compiled
php artisan optimize:clear
```

### Contact

- **Technical Lead**: tech@studai-career.com
- **DevOps**: devops@studai-career.com
- **Emergency**: +1-XXX-XXX-XXXX

---

**Last Updated**: {{ now()->format('F j, Y') }}  
**Version**: 1.0.0  
**Laravel Version**: 11.x
