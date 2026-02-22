# StudAI Career - CLAUDE.md Patch Document

> **Purpose:** Updates and additions to make CLAUDE.md accurate and authoritative
> **Version:** 1.0
> **Date:** February 2026

---

## How to Apply This Patch

This document contains sections that should be updated in or added to the main CLAUDE.md file. Each section is marked with:

- `[REPLACE]` - Replace the existing section entirely
- `[ADD]` - Add this as a new section
- `[UPDATE]` - Update specific values within the existing section

---

## Section 1: Platform Scale [REPLACE]

**Location:** Section 1, "Platform Scale" table

**Replace with:**

```markdown
### Platform Scale [Authoritative]

> Last verified: February 2026

| Metric | Count | Status |
|--------|-------|--------|
| Eloquent Models | 227 | Verified |
| Database Migrations | 71 | Verified |
| API Routes | 150+ | Verified |
| Service Classes | 108 | Verified |
| HTTP Controllers | 76 | Verified |
| Background Jobs | 37 | Verified |
| Livewire Components | 24 | Verified |
| Filament Resources | 87 | Verified |
| Test Files | 28 | Needs Improvement |
| Lines of Code | ~25,000 | Estimated |

**Verification Commands:**
```powershell
# Count models
(Get-ChildItem -Path app\Models -Filter *.php -Recurse).Count

# Count services
(Get-ChildItem -Path app\Services -Filter *.php -Recurse).Count

# Count controllers
(Get-ChildItem -Path app\Http\Controllers -Filter *.php -Recurse).Count

# Count migrations
(Get-ChildItem -Path database\migrations -Filter *.php).Count
```
```

---

## Section 2: Search Configuration [UPDATE]

**Location:** Section 2, Technology Stack, Search Engine

**Update the following:**

```markdown
### Database & Caching [Authoritative]

| Component | Technology | Status |
|-----------|------------|--------|
| **Primary Database** | MySQL | 8.0+ Active |
| **Cache & Sessions** | Redis | Configured |
| **Search Engine** | Meilisearch | **INACTIVE** - driver set to `collection` |
| **Vector Search** | pgvector | **PLANNED** - not yet implemented |

> **IMPORTANT:** The Scout driver is currently set to `collection` in `config/scout.php`.
> Meilisearch is configured but not active. Vector semantic search has not been implemented.
```

---

## Section 3: Agent Services Warning [ADD]

**Location:** After Section 7.3 (Agent Services)

**Add new section:**

```markdown
### Agent Services [CRITICAL WARNING]

> ⚠️ **PRODUCTION READINESS: NOT READY**

The autonomous job agent system has the following critical limitations:

#### Scraper Services are Demo/Placeholder Only

| Service | Status | Notes |
|---------|--------|-------|
| `LinkedInScraperService` | **DEMO** | Returns hardcoded demo data (line 246-287) |
| `IndeedScraperService` | **DEMO** | Returns hardcoded demo data |
| `GlassdoorScraperService` | **DEMO** | Returns hardcoded demo data |
| `RSSFeedParser` | **DEMO** | Returns hardcoded demo data |

#### What This Means
- The agent CANNOT discover real jobs from external sources
- Only jobs manually added to the platform can be matched
- Auto-apply functionality depends on non-functional scrapers

#### Legal/Compliance Risk
Scraping LinkedIn, Indeed, or Glassdoor without official API access violates their Terms of Service and may result in:
- IP blocking
- Legal action
- Account termination

#### Required for Production
1. **LinkedIn Partner API** - Apply at https://developer.linkedin.com/
2. **Indeed Publisher API** - Apply for Publisher Program
3. **Official Job Board APIs** - Use authorized data feeds only
4. **RSS Aggregation** - Only for boards with public RSS feeds (RemoteOK, WeWorkRemotely)

#### Safety Features Missing
- [ ] Kill-switch mechanism
- [ ] Human-in-the-loop approval gates
- [ ] Per-user daily hard caps (beyond soft limits)
- [ ] Comprehensive audit logging
```

---

## Section 4: Event-Driven Architecture [REPLACE]

**Location:** Section 8.8, Event-Driven Processing

**Replace with:**

```markdown
### 8.8 Event-Driven Processing [Partial Implementation]

> **Status:** Minimal implementation. Most side effects handled inline.

#### Currently Implemented Events

| Event | Listeners | Purpose |
|-------|-----------|---------|
| `ApplicationSubmitted` | `SendApplicationSubmittedNotification` | Notify employer |
| `ApplicationStatusChanged` | `SendApplicationStatusChangedNotification` | Notify applicant |
| `Registered` (Laravel) | `SendEmailVerificationNotification` | Email verification |

#### Event Subscriber

| Subscriber | Events Handled |
|------------|----------------|
| `GamificationEventSubscriber` | Multiple events for points/badges |

#### Events That SHOULD Be Implemented

The following events are recommended for true event-driven architecture:

**User Lifecycle:**
- `UserRegistered`
- `ProfileCompleted`
- `ResumeUploaded`
- `ResumeAnalyzed`

**Payments:**
- `PaymentInitiated`
- `PaymentSucceeded`
- `PaymentFailed`
- `SubscriptionActivated`
- `SubscriptionCanceled`

**Agent:**
- `AgentActivated`
- `AgentJobDiscovered`
- `AgentApplicationSubmitted`

**SCOUT:**
- `CandidateShortlisted`
- `PredictionGenerated`
- `BiasAuditCompleted`

**Learning:**
- `SkillGapIdentified`
- `LearningPathStarted`
- `LearningPathCompleted`

#### Current Reality

Most side effects are handled **inline** in controllers and services:
- Email sending
- Points awarding
- Activity logging
- Notification dispatch

This limits:
- Decoupling and testability
- Audit trail completeness
- Async processing capabilities
```

---

## Section 5: Enterprise Infrastructure [ADD]

**Location:** Add as new Section 12

```markdown
---

## 12. Enterprise Infrastructure [Conceptual]

> **Status:** Planned for implementation. These are architectural requirements, not current state.

### 12.1 Vector Semantic Search Layer

**Recommended Architecture:** PostgreSQL + pgvector for MVP, migrate to Qdrant for scale.

**Required Tables:**
```sql
-- Job embeddings for semantic search
CREATE TABLE job_embeddings (
    id BIGSERIAL PRIMARY KEY,
    job_id BIGINT REFERENCES job_listings(id) ON DELETE CASCADE,
    embedding vector(1536),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(job_id)
);

-- User profile embeddings for matching
CREATE TABLE user_profile_embeddings (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    embedding vector(1536),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id)
);
```

**Services Required:**
```php
// Generate embeddings via Azure OpenAI
class EmbeddingService
{
    public function generate(string $text): array;
    public function generateBatch(array $texts): array;
}

// Query similar items by vector
class VectorSearchService
{
    public function findSimilar(array $embedding, int $limit = 10): Collection;
    public function cosineSimilarity(array $a, array $b): float;
}

// Combine keyword + vector search
class HybridSearchService
{
    public function search(string $query, array $filters): Collection;
}
```

**Hybrid Retrieval Strategy:**
```
1. Meilisearch keyword search → Top 100 candidates
2. Vector similarity search → Top 100 candidates
3. Reciprocal Rank Fusion (RRF) → Merged ranked list
4. Optional reranker model → Final top 20
```

### 12.2 AI Governance & Evaluation

**Prompt Registry:**
- All AI prompts stored in `ai_prompts` table
- Version controlled with `is_active` flag
- Supports A/B testing via `metadata` field

**Golden Test Sets:**
```php
// ai_golden_tests table
Schema::create('ai_golden_tests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_id')->constrained('ai_prompts');
    $table->string('test_name');
    $table->json('input');
    $table->json('expected_output');
    $table->float('similarity_threshold')->default(0.8);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Evaluation Pipeline:**
- Run golden tests on deployment (CI/CD gate)
- Compare AI outputs to expected results
- Fail deployment if regression exceeds threshold
- Alert on model drift (scheduled job)

**Decision Traceability:**
- SCOUT predictions store `explanation_json`
- Include input features, weights, decision factors
- Support compliance audits and explainable AI requirements

### 12.3 Observability Stack

| Component | Recommended Tool | Purpose |
|-----------|------------------|---------|
| Error Tracking | Sentry | Exception capture, alerting |
| Logging | Structured JSON | Parseable logs with context |
| Queue Monitoring | Laravel Horizon | Job metrics, failed jobs |
| APM | New Relic / Datadog | Performance monitoring |

**Correlation ID Implementation:**
```php
// CorrelationIdMiddleware.php
class CorrelationIdMiddleware
{
    public function handle($request, $next)
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid();

        Context::add('correlation_id', $correlationId);
        Log::shareContext(['correlation_id' => $correlationId]);

        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
```

### 12.4 Agent Safety Doctrine

**Required Guardrails:**

| Guardrail | Description | Implementation |
|-----------|-------------|----------------|
| `require_approval` | User must approve each application | `agent_configurations.require_approval` |
| `daily_cap` | Hard limit on daily applications | Check + enforce in job |
| `emergency_stop` | Instant kill switch per user | `agent_configurations.emergency_stop` |
| Global Kill-All | Admin can disable all agents | `POST /admin/agent/kill-all` |

**Audit Requirements:**
```php
// Every agent action must be logged
Schema::create('agent_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('action'); // discover, match, apply, skip, error
    $table->foreignId('discovered_job_id')->nullable();
    $table->string('result'); // success, failed, skipped, pending
    $table->json('metadata');
    $table->string('correlation_id')->index();
    $table->timestamps();
});
```

**Legal Compliance:**
| Platform | Scraping Status | Recommended Approach |
|----------|-----------------|---------------------|
| LinkedIn | **PROHIBITED** | Use Partner API only |
| Indeed | **PROHIBITED** | Use Publisher API |
| Glassdoor | **PROHIBITED** | Partner feeds only |
| RemoteOK | Allowed | RSS feed |
| WeWorkRemotely | Allowed | RSS feed |

### 12.5 Global Payments Readiness

**Gateway Matrix:**

| Gateway | Region | Methods | Status |
|---------|--------|---------|--------|
| Razorpay | India | Card, UPI, NetBanking, Wallet | ✅ Active |
| PayU | India | Card, UPI, NetBanking | ✅ Active |
| Stripe | Global | Card, ACH, SEPA, Apple/Google Pay | 🔲 Planned |

**Subscription State Machine:**
```
           ┌──────────────┐
           │   trialing   │
           └──────┬───────┘
                  │ activate()
                  ▼
           ┌──────────────┐         ┌──────────────┐
     ┌─────│    active    │◄─pay()──│   past_due   │
     │     └──────┬───────┘         └──────────────┘
     │            │ pause()                 │
     │            ▼                         │ fail 3x
     │     ┌──────────────┐                 │
     │     │    paused    │                 │
     │     └──────┬───────┘                 │
     │            │ resume()                │
     │            └─────────────────────────┤
     │ cancel()                             │
     ▼                                      ▼
┌──────────────┐                    ┌──────────────┐
│   canceled   │◄───────────────────│   canceled   │
└──────────────┘                    └──────────────┘
```

**Required Features:**
- [ ] Webhook signature verification (all gateways)
- [ ] Idempotency keys for all payment operations
- [ ] Proration for plan upgrades/downgrades
- [ ] Grace period (3 days) after payment failure
- [ ] Automatic retry with exponential backoff
```

---

## Section 6: Verification Commands [ADD]

**Location:** Add as new Section 13

```markdown
---

## 13. Verification Commands

Use these commands to generate accurate metrics and verify documentation claims.

### Codebase Metrics

```powershell
# Windows PowerShell

# Count Eloquent models
(Get-ChildItem -Path app\Models -Filter *.php -Recurse).Count

# Count Service classes
(Get-ChildItem -Path app\Services -Filter *.php -Recurse).Count

# Count HTTP Controllers
(Get-ChildItem -Path app\Http\Controllers -Filter *.php -Recurse).Count

# Count Background Jobs
(Get-ChildItem -Path app\Jobs -Filter *.php -Recurse).Count

# Count Database Migrations
(Get-ChildItem -Path database\migrations -Filter *.php).Count

# Count Test Files
(Get-ChildItem -Path tests -Filter *Test.php -Recurse).Count

# Count Events
(Get-ChildItem -Path app\Events -Filter *.php -Recurse).Count

# Count Listeners
(Get-ChildItem -Path app\Listeners -Filter *.php -Recurse).Count
```

### Laravel Artisan Commands

```bash
# List all routes (JSON export)
php artisan route:list --json > routes.json

# List all routes (table format)
php artisan route:list

# Check migration status
php artisan migrate:status

# Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate API documentation
php artisan l5-swagger:generate

# Run test suite with coverage
php artisan test --coverage

# Check PHP syntax for all files
Get-ChildItem -Path app -Filter *.php -Recurse | ForEach-Object { php -l $_.FullName }
```

### Database Schema Verification

```bash
# Export schema (requires mysqldump)
mysqldump -u $DB_USERNAME -p $DB_DATABASE --no-data > schema.sql

# List all tables
php artisan tinker --execute="DB::select('SHOW TABLES')"

# List all indexes
php artisan tinker --execute="DB::select('SHOW INDEX FROM job_listings')"
```

### Health Checks

```bash
# Check if app boots without errors
php artisan config:clear && php artisan serve --no-interaction &

# Check queue health
php artisan queue:monitor

# Check Horizon status (if installed)
php artisan horizon:status
```
```

---

## Section 7: Quick Reference Updates [UPDATE]

**Location:** Quick Reference Card at end of document

**Update the following values:**

```markdown
## Quick Reference Card [Updated February 2026]

### Verified Metrics
| Metric | Count |
|--------|-------|
| Models | 227 |
| Services | 108 |
| Controllers | 76 |
| Jobs | 37 |
| Migrations | 71 |
| API Routes | 150+ |

### Known Limitations
| Feature | Status | Notes |
|---------|--------|-------|
| Meilisearch | INACTIVE | Driver = collection |
| Vector Search | NOT IMPLEMENTED | Planned |
| Job Scrapers | DEMO ONLY | Returns fake data |
| Event System | MINIMAL | Only 2 events |
| Test Coverage | ~15% | Needs improvement |

### Key File Locations
| Purpose | Path |
|---------|------|
| AI Config | `config/ai.php` |
| Payment Config | `config/payment.php` |
| Scout Config | `config/scout.php` (driver=collection!) |
| Main AI Service | `app/Services/AI/AIService.php` |
| Payment Service | `app/Services/PaymentGatewayService.php` |
| Agent Demo Data | `app/Services/Agent/LinkedInScraperService.php:246` |
```

---

## Changelog Summary

| Section | Change Type | Description |
|---------|-------------|-------------|
| Platform Scale | REPLACE | Updated to verified counts |
| Search Configuration | UPDATE | Marked Meilisearch as INACTIVE |
| Agent Services | ADD | Added critical warning about demo scrapers |
| Event-Driven Processing | REPLACE | Documented minimal implementation |
| Enterprise Infrastructure | ADD | New section with planned architecture |
| Verification Commands | ADD | Commands to verify claims |
| Quick Reference | UPDATE | Corrected metrics and added limitations |

---

## Application Instructions

1. Create backup of current CLAUDE.md
2. Apply REPLACE sections by copying new content
3. Apply ADD sections at specified locations
4. Apply UPDATE sections by modifying existing values
5. Review entire document for consistency
6. Commit changes with message: "docs: Update CLAUDE.md with verified metrics and enterprise architecture"

---

*This patch document generated from comprehensive codebase analysis conducted February 2026.*
