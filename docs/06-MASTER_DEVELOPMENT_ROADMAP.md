# StudAI Career — Master Development Roadmap

> **Version:** 1.0  
> **Created:** February 6, 2026  
> **Derived From:** Completeness Scorecard, Reality vs Claims, System Blueprint, Implementation Backlog, CLAUDE.md Patch  
> **Goal:** Take the platform from 70-75% prototype → 100% enterprise-grade production deployment  
> **Total Estimated Duration:** 8 weeks (6 core + 2 buffer/polish)

---

## Executive Summary

StudAI Career is a strong 70-75% complete platform with excellent AI integration, data modeling (227 models, 71 migrations), and API design (150+ endpoints). However, **four critical domains** score 3-5/10 (Agent System, Observability, Events, Testing) and block production readiness.

This roadmap breaks the remaining work into **6 phases** executed sequentially, each building on the previous. Every task includes what to build, which files to touch, acceptance criteria, and effort in days.

### Current State at a Glance

| What's Strong (7-10/10) | What's Broken (3-5/10) |
|---|---|
| AI Layer (Azure OpenAI + Anthropic fallback) | Agent scrapers are demo stubs returning fake data |
| Data Model (227 models, 71 migrations) | Only 2 events, 3 listeners — not event-driven |
| API Design (150+ endpoints, auth, rate limiting) | No Sentry, no correlation IDs, no Horizon |
| S.C.O.U.T. Employer (14+ services) | 28 test files — ~15% coverage for 227 models |
| Auth (Sanctum + Fortify + 2FA + Spatie) | Scout driver = `collection`, not Meilisearch |
| Payments (Razorpay + PayU working) | No Stripe for global expansion |

---

## Phase Overview

| Phase | Name | Duration | Theme | Key Outcome |
|-------|------|----------|-------|-------------|
| **1** | Observability & Stability | Week 1 | See what's happening | Error tracking, structured logging, health checks |
| **2** | Search & AI Governance | Week 2 | Smart search, safe AI | Meilisearch active, vector search, prompt registry |
| **3** | Agent Safety & Real Data | Week 3 | Make it real and safe | Kill-switch, audit logs, real job APIs |
| **4** | Payments & Subscriptions | Week 4 | Take money globally | Stripe, subscription state machine, proration |
| **5** | Testing & Security | Week 5 | Trust the code | 70%+ coverage, GDPR, security hardening |
| **6** | Event Architecture & Docs | Week 6 | Clean architecture | 15+ events, OpenAPI spec, accurate docs |

---

## Phase 1 — Observability & Stability Foundation

**Duration:** 5 days  
**Goal:** Go from "production blind" to full visibility into errors, requests, and queue health.  
**Current Score:** Observability 3/10 → Target 8/10

### Why First?
You cannot safely fix anything else if you can't see what's breaking. Every subsequent phase benefits from having Sentry, correlation IDs, and Horizon in place.

### Tasks

#### 1.1 Install & Configure Sentry Error Tracking
**Effort:** 0.5 day  
**What:** Install `sentry/sentry-laravel`, configure DSN, environment tagging, user context capture.  
**Files to create/modify:**
- `composer.json` — add `sentry/sentry-laravel`
- `config/sentry.php` — DSN, environment, traces_sample_rate
- `.env` / `.env.example` — add `SENTRY_LARAVEL_DSN`
- `app/Exceptions/Handler.php` — ensure Sentry captures all unhandled exceptions

**Acceptance Criteria:**
- [ ] Unhandled exceptions appear in Sentry dashboard with full stack trace
- [ ] User context (ID, email) attached to error reports
- [ ] Environment tags (local/staging/production) set correctly

---

#### 1.2 Implement Correlation ID Middleware
**Effort:** 0.5 day  
**What:** Generate a UUID per request, propagate through logs, attach to response headers. This enables end-to-end request tracing.  
**Files to create/modify:**
- `app/Http/Middleware/CorrelationIdMiddleware.php` — new file
- `app/Http/Kernel.php` — register in global middleware stack
- Every log call now includes `correlation_id` automatically via `Log::shareContext()`

**Implementation:**
```php
class CorrelationIdMiddleware
{
    public function handle($request, $next)
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        Context::add('correlation_id', $correlationId);
        Log::shareContext(['correlation_id' => $correlationId]);
        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);
        return $response;
    }
}
```

**Acceptance Criteria:**
- [ ] Every response includes `X-Correlation-ID` header
- [ ] All log entries contain `correlation_id` field
- [ ] Incoming requests with `X-Correlation-ID` header reuse that ID

---

#### 1.3 Switch to Structured JSON Logging
**Effort:** 0.5 day  
**What:** Configure Laravel logging to output JSON. Include correlation_id, user_id, request_uri, timestamp in every log entry.  
**Files to modify:**
- `config/logging.php` — add JSON formatter channel, set as default for production
- Verify all existing `Log::info/error/warning` calls work with new format

**Acceptance Criteria:**
- [ ] Production logs output as parseable JSON lines
- [ ] Each log line includes: `correlation_id`, `user_id`, `request_uri`, `timestamp`, `level`, `message`

---

#### 1.4 Install & Configure Laravel Horizon
**Effort:** 1 day  
**What:** Install Horizon for queue monitoring, configure queue priorities, set up dashboard auth.  
**Prerequisites:** Redis must be running and configured.  
**Files to create/modify:**
- `composer.json` — add `laravel/horizon`
- `config/horizon.php` — configure supervisors, balancing, environments
- `app/Providers/HorizonServiceProvider.php` — dashboard authorization gate
- `routes/web.php` — Horizon route (auto-registered by package)

**Queue Priority Structure to implement:**
```
high     → Payments, auth, critical notifications
default  → Standard user operations
low      → Background analytics, reports
ai       → AI-intensive operations (longer timeout)
search   → Indexing, embedding generation
```

**Acceptance Criteria:**
- [ ] Horizon dashboard accessible at `/horizon` (admin only)
- [ ] Queue workers processing jobs with correct priorities
- [ ] Failed jobs visible in Horizon with retry capability
- [ ] Auto-balancing configured for production workload

---

#### 1.5 Implement Circuit Breaker for AI Services
**Effort:** 1 day  
**What:** Wrap AI API calls in a circuit breaker pattern. Track consecutive failures, open circuit after 3, auto-reset after 60 seconds. Prevents cascading failures when Azure OpenAI or Anthropic is down.  
**Files to create/modify:**
- `app/Services/AI/CircuitBreaker.php` — new class
- `app/Services/AI/AIService.php` — integrate circuit breaker into `callAI()` method
- `config/ai.php` — add circuit breaker settings (threshold, timeout)

**States:**
```
CLOSED (normal) → 3 failures → OPEN (reject all) → 60s → HALF_OPEN (try one) → success → CLOSED
```

**Acceptance Criteria:**
- [ ] AI calls fail fast (no 30s timeout) when circuit is OPEN
- [ ] Circuit auto-resets after 60 seconds
- [ ] Fallback response returned when circuit is open
- [ ] Circuit state logged to Sentry/structured logs

---

#### 1.6 Add Idempotency Key Middleware for Payments
**Effort:** 1 day  
**What:** Prevent duplicate payment processing. Store idempotency key → response mapping in Redis/cache. Return cached response for duplicate requests.  
**Files to create/modify:**
- `app/Http/Middleware/IdempotencyMiddleware.php` — new file
- `app/Http/Kernel.php` — register for payment route group
- `routes/web.php` or `routes/api.php` — apply middleware to payment endpoints

**Acceptance Criteria:**
- [ ] Duplicate POST requests to payment endpoints return cached response
- [ ] Idempotency keys stored in Redis with 24-hour TTL
- [ ] Each key is scoped to user + endpoint

---

#### 1.7 Dead-Letter Queue Strategy
**Effort:** 0.5 day  
**What:** Configure failed job retention, create artisan command to process/alert on failed jobs, set up Slack/email alerting.  
**Files to create/modify:**
- `app/Console/Commands/ProcessFailedJobsCommand.php` — new command
- `app/Console/Kernel.php` — schedule hourly check
- `config/queue.php` — failed job retention settings

**Acceptance Criteria:**
- [ ] Failed jobs retained for 30 days
- [ ] Alert sent (email/Slack) when failed job count > 5 in an hour
- [ ] Admin can retry/delete failed jobs via Horizon

---

#### 1.8 Health Check Endpoints
**Effort:** 0.5 day  
**What:** Create `/health` (basic liveness) and `/ready` (full readiness with DB/Redis/Queue checks) endpoints.  
**Files to create/modify:**
- `app/Http/Controllers/HealthCheckController.php` — new controller
- `routes/web.php` — register `/health` and `/ready` routes (no auth)

**Acceptance Criteria:**
- [ ] `GET /health` returns 200 with `{"status": "ok"}`
- [ ] `GET /ready` returns 200 only if MySQL, Redis, and Queue are healthy
- [ ] `GET /ready` returns 503 with details if any dependency is down

---

### Phase 1 Checklist
- [ ] All 8 tasks merged
- [ ] CI pipeline passes
- [ ] Staging deployment successful with Sentry capturing test errors
- [ ] Horizon dashboard showing queue metrics

---

## Phase 2 — Search & AI Governance

**Duration:** 5 days  
**Goal:** Enable real search (Meilisearch + vector), establish AI prompt management and evaluation pipeline.  
**Current Score:** Search 6/10, AI Layer 8/10 → Target 9/10 each

### Why Second?
Search is the core UX — users need to find jobs. AI governance prevents silent quality degradation in your 50+ AI service calls.

### Tasks

#### 2.1 Enable Meilisearch Driver in Scout
**Effort:** 0.5 day  
**What:** Change Scout driver from `collection` to `meilisearch`. Configure Meilisearch connection. Import existing Job records.  
**Files to modify:**
- `config/scout.php` — change `'driver' => env('SCOUT_DRIVER', 'meilisearch')`
- `.env` / `.env.example` — add `MEILISEARCH_HOST`, `MEILISEARCH_KEY`
- Run: `php artisan scout:import "App\Models\JobListing"`

**Prerequisites:** Meilisearch server running (Docker or installed).

**Acceptance Criteria:**
- [ ] `config/scout.php` driver = `meilisearch`
- [ ] Job search returns results from Meilisearch index
- [ ] Search latency < 50ms for keyword queries

---

#### 2.2 Create Job Embeddings Infrastructure
**Effort:** 1 day  
**What:** Create migration for `job_embeddings` table with vector column. Build `EmbeddingService` using Azure OpenAI `text-embedding-3-large`.  
**Files to create/modify:**
- `database/migrations/xxxx_create_job_embeddings_table.php` — new migration
- `app/Services/AI/EmbeddingService.php` — new service
- `app/Models/JobEmbedding.php` — new model
- `config/ai.php` — add embedding model configuration

**Migration:**
```php
Schema::create('job_embeddings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('job_listing_id')->constrained()->onDelete('cascade');
    $table->json('embedding'); // Store as JSON for MySQL, migrate to pgvector for PostgreSQL
    $table->timestamps();
    $table->unique('job_listing_id');
});
```

**Note:** For MySQL, store embeddings as JSON and compute similarity in PHP. For PostgreSQL, use pgvector extension for native vector operations.

**Acceptance Criteria:**
- [ ] `EmbeddingService::generate(string $text): array` returns 1536-dim vector
- [ ] `EmbeddingService::generateBatch(array $texts): array` processes up to 100 texts
- [ ] Embedding generation available as queued job

---

#### 2.3 Create GenerateJobEmbeddings Job
**Effort:** 0.5 day  
**What:** Queued job to generate embeddings for all existing jobs. Process in batches of 100. Track progress. Handle failures gracefully.  
**Files to create:**
- `app/Jobs/GenerateJobEmbeddingsJob.php` — new job
- Schedule in `app/Console/Kernel.php` for new jobs

**Acceptance Criteria:**
- [ ] Batch processes 100 jobs at a time
- [ ] Failed batches don't block remaining batches
- [ ] Progress logged (e.g., "Processed 500/2000 jobs")

---

#### 2.4 Implement HybridSearchService
**Effort:** 1.5 days  
**What:** Combine Meilisearch keyword results with vector similarity results using Reciprocal Rank Fusion (RRF).  
**Files to create:**
- `app/Services/Search/HybridSearchService.php` — new service
- `app/Services/Search/VectorSearchService.php` — new service
- Update `app/Http/Controllers/JobController.php` — use HybridSearchService

**Search Flow:**
```
User query → 
  1. Meilisearch keyword search → Top 100 keyword matches
  2. Embed query → Vector similarity → Top 100 semantic matches  
  3. Reciprocal Rank Fusion → Merged ranked list
  4. Return top 20 results
```

**Acceptance Criteria:**
- [ ] "ML role" finds "Machine Learning Engineer" (semantic match)
- [ ] "remote python developer" finds keyword + semantic results
- [ ] Search latency < 200ms for hybrid queries
- [ ] Graceful fallback to keyword-only if vector search fails

---

#### 2.5 Create Prompt Registry System
**Effort:** 1 day  
**What:** Store all AI prompts in a database table with version control. Replace hardcoded prompts throughout codebase.  
**Files to create/modify:**
- `database/migrations/xxxx_create_ai_prompts_table.php` — new migration
- `app/Models/AIPrompt.php` — new model
- `app/Services/AI/PromptRegistryService.php` — new service with `get(name)`, `getActive(name)`, `create(data)`, `setActive(name, version)`
- Scan and update all services in `app/Services/AI/` that have hardcoded prompts

**Migration:**
```php
Schema::create('ai_prompts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->integer('version')->default(1);
    $table->text('template');
    $table->text('system_prompt')->nullable();
    $table->boolean('is_active')->default(true);
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->unique(['name', 'version']);
});
```

**Acceptance Criteria:**
- [ ] All AI prompts loaded from `ai_prompts` table (not hardcoded)
- [ ] Version history maintained — can roll back to previous prompt
- [ ] Prompts cached (Redis) with cache invalidation on update
- [ ] Admin can manage prompts via Filament resource

---

#### 2.6 Build AI Golden Test Suite
**Effort:** 1 day  
**What:** Create golden test infrastructure to detect AI quality regressions. Store expected inputs/outputs. Run as CI gate.  
**Files to create:**
- `database/migrations/xxxx_create_ai_golden_tests_table.php` — new migration
- `app/Models/AIGoldenTest.php` — new model
- `app/Services/AI/AIEvaluationService.php` — new service
- `app/Console/Commands/RunAIGoldenTestsCommand.php` — artisan command
- `tests/Feature/AI/GoldenTestSuiteTest.php` — PHPUnit integration

**Acceptance Criteria:**
- [ ] At least 10 golden tests covering: resume analysis, interview questions, cover letter, skill gap, negotiation
- [ ] `php artisan ai:golden-tests` runs all tests and reports pass/fail
- [ ] CI pipeline fails if golden test similarity drops below 0.8 (configurable threshold)
- [ ] Results logged with correlation IDs for debugging

---

#### 2.7 Filament Admin for AI Prompts
**Effort:** 0.5 day  
**What:** Create Filament resource to manage AI prompts — CRUD, version comparison, activate/deactivate.  
**Files to create:**
- `app/Filament/Resources/AIPromptResource.php`
- `app/Filament/Resources/AIPromptResource/Pages/ListAIPrompts.php`
- `app/Filament/Resources/AIPromptResource/Pages/CreateAIPrompt.php`
- `app/Filament/Resources/AIPromptResource/Pages/EditAIPrompt.php`

**Acceptance Criteria:**
- [ ] Admin can create, edit, activate/deactivate prompts
- [ ] Version history visible in table
- [ ] Cache cleared on prompt update

---

### Phase 2 Checklist
- [ ] Meilisearch active and returning results
- [ ] Vector embeddings generated for all jobs
- [ ] Hybrid search working with RRF
- [ ] Prompt registry with versioning operational
- [ ] Golden test suite passing in CI
- [ ] All 7 tasks merged

---

## Phase 3 — Agent Safety & Real Data Sources

**Duration:** 7 days  
**Goal:** Transform the agent from a dangerous demo into a production-safe system with real job data.  
**Current Score:** Agent 5/10 → Target 8/10

### Why Third?
The agent is the platform's differentiator but currently returns **fake data** and has **no safety controls**. This is the highest legal and reputational risk.

### Tasks

#### 3.1 Implement Agent Kill-Switch
**Effort:** 1 day  
**What:** Add `emergency_stop` column to `agent_configurations`. Create middleware that checks before any agent action. Create admin "kill all agents" endpoint.  
**Files to create/modify:**
- `database/migrations/xxxx_add_emergency_stop_to_agent_configurations.php`
- `app/Http/Middleware/AgentKillSwitchMiddleware.php` — new file
- `app/Http/Controllers/Admin/AgentAdminController.php` — new controller
- `routes/api.php` — add `POST /admin/agent/kill-all`
- `app/Jobs/ProcessAutoApplications.php` — check kill-switch before processing

**Acceptance Criteria:**
- [ ] Individual user agent stops immediately when `emergency_stop = true`
- [ ] Admin `POST /admin/agent/kill-all` stops ALL agents platform-wide
- [ ] Kill-switch check happens BEFORE any external API call
- [ ] Audit log entry created when kill-switch activates

---

#### 3.2 Implement Human-in-the-Loop Approval
**Effort:** 1 day  
**What:** When `require_approval = true`, agent creates pending applications for user review instead of auto-applying.  
**Files to modify:**
- `app/Jobs/ProcessAutoApplications.php` — add approval gate logic
- `app/Http/Controllers/AgentController.php` — add approve/reject endpoints
- `app/Notifications/AgentApprovalRequestNotification.php` — new notification
- `routes/api.php` — add approval endpoints

**Acceptance Criteria:**
- [ ] When `require_approval = true`, applications go to "pending_approval" status
- [ ] User receives notification with job details and one-click approve/reject
- [ ] Approved applications proceed to submission
- [ ] Rejected applications are logged and skipped

---

#### 3.3 Enforce Daily Application Hard Cap
**Effort:** 0.5 day  
**What:** Track daily application count. Enforce hard limit even if job runs multiple times. Reset at midnight UTC.  
**Files to modify:**
- `app/Jobs/ProcessAutoApplications.php` — add daily count check
- `app/Models/AgentConfiguration.php` — add `applications_today` tracking

**Acceptance Criteria:**
- [ ] Agent stops after reaching daily limit (e.g., 10/day)
- [ ] Counter resets at midnight UTC via scheduled command
- [ ] Limit enforced even if ProcessAutoApplications runs multiple times

---

#### 3.4 Create Comprehensive Agent Audit Log
**Effort:** 1 day  
**What:** Log every agent action with full context: discover, match, apply, skip, error. Include correlation IDs for tracing.  
**Files to create:**
- `database/migrations/xxxx_create_agent_audit_logs_table.php`
- `app/Models/AgentAuditLog.php`
- `app/Services/Agent/AgentAuditService.php` — centralized logging
- Update all agent services to use `AgentAuditService`

**Migration:**
```php
Schema::create('agent_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('action'); // discover, match, apply, skip, error
    $table->foreignId('discovered_job_id')->nullable()->constrained()->onDelete('set null');
    $table->string('result'); // success, failed, skipped, pending_approval
    $table->json('metadata')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
    $table->index(['user_id', 'created_at']);
    $table->index(['action', 'result']);
});
```

**Acceptance Criteria:**
- [ ] Every agent action creates an audit log entry
- [ ] Metadata includes: match_score, job_source, failure_reason (if applicable)
- [ ] Admin can query audit logs via Filament
- [ ] Correlation ID links audit entries to request logs

---

#### 3.5 Replace Scrapers with Official API Adapters
**Effort:** 3 days  
**What:** Remove demo/stub scrapers. Implement real data sources via official APIs and public RSS feeds.  
**Files to create/modify:**

| Task | File | Effort | Priority |
|------|------|--------|----------|
| RSS feed aggregator (RemoteOK, WeWorkRemotely, etc.) | `app/Services/Agent/RSSJobFeedService.php` | 0.5 day | P0 — Free, no API key |
| Indeed Publisher API integration | `app/Services/Agent/IndeedPublisherService.php` | 1 day | P1 — Requires API key |
| LinkedIn Partner API integration | `app/Services/Agent/LinkedInPartnerAPIService.php` | 1 day | P1 — Requires partnership |
| Update JobAggregationService | `app/Services/Agent/JobAggregationService.php` | 0.5 day | P0 |
| Mark old scrapers as deprecated | `*ScraperService.php` files | — | P0 |

**RSS Sources (No API Key Required):**
- RemoteOK: `https://remoteok.com/remote-jobs.rss`  
- WeWorkRemotely: `https://weworkremotely.com/remote-jobs.rss`  
- HackerNews Jobs: `https://hn.algolia.com/api/v1/search_by_date?tags=job`
- GitHub Jobs: Community RSS feeds

**Acceptance Criteria:**
- [ ] At least 2 real job sources returning actual listings
- [ ] RSS feed aggregator running on hourly schedule
- [ ] Old scraper services marked `@deprecated` with clear documentation
- [ ] `JobAggregationService` uses new sources instead of stubs
- [ ] No scraping of LinkedIn/Indeed/Glassdoor without official API access

---

#### 3.6 Implement Job Source Quality Scoring
**Effort:** 0.5 day  
**What:** Track application success rates per source. Score sources. Prioritize high-quality sources.  
**Files to create/modify:**
- `app/Services/Agent/JobSourceScoringService.php` — new service
- `app/Models/JobSource.php` — add `quality_score` column if not exists

**Acceptance Criteria:**
- [ ] Each job source has a quality score (0-100)
- [ ] Score updated based on: application success rate, listing accuracy, response rate
- [ ] Low-quality sources deprioritized in aggregation

---

#### 3.7 Enforce Company Blacklist
**Effort:** 0.5 day  
**What:** Block auto-applications to blacklisted companies. Log reason in audit trail.  
**Files to modify:**
- `app/Services/Agent/JobMatchingService.php` — add blacklist check in `calculateMatch()`
- Return `score = 0` for blacklisted companies

**Acceptance Criteria:**
- [ ] Blacklisted companies never receive auto-applications
- [ ] Blacklist reason stored in agent audit log
- [ ] Users can manage their personal blacklist

---

### Phase 3 Checklist
- [ ] Kill-switch functional (per-user + global)
- [ ] Human-in-the-loop approval working
- [ ] Daily cap enforced
- [ ] Full audit logging for every agent action
- [ ] At least 2 real job sources returning actual listings
- [ ] Old scrapers deprecated and documented
- [ ] All 7 tasks merged

---

## Phase 4 — Payments & Subscription Hardening

**Duration:** 5 days  
**Goal:** Add Stripe for global payments, implement proper subscription lifecycle management.  
**Current Score:** Payments 7/10 → Target 9/10

### Tasks

#### 4.1 Integrate Stripe Payment Gateway
**Effort:** 2 days  
**What:** Install `stripe/stripe-php`. Create `StripeGatewayService` with checkout sessions, subscription management, webhook handling.  
**Files to create/modify:**
- `composer.json` — add `stripe/stripe-php`
- `config/payment.php` — add Stripe gateway configuration
- `config/stripe.php` — Stripe-specific settings
- `.env` / `.env.example` — `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `app/Services/Payment/StripeGatewayService.php` — new service
- `app/Http/Controllers/Webhooks/StripeWebhookController.php` — new controller
- `routes/web.php` — add `POST /webhooks/stripe`

**Webhook Events to Handle:**
- `checkout.session.completed` — Payment successful
- `invoice.paid` — Subscription renewed
- `invoice.payment_failed` — Payment failed
- `customer.subscription.updated` — Plan changed
- `customer.subscription.deleted` — Subscription canceled

**Acceptance Criteria:**
- [ ] Users can pay via Stripe (card, Apple Pay, Google Pay)
- [ ] Gateway auto-selected based on user region (India → Razorpay, Global → Stripe)
- [ ] Webhook signature verified before processing
- [ ] All webhook events handled and logged

---

#### 4.2 Implement Subscription State Machine
**Effort:** 1 day  
**What:** Create a proper state machine for subscription lifecycle. States: `trialing`, `active`, `past_due`, `canceled`, `paused`. Enforce valid transitions only.  
**Files to create:**
- `app/Services/Subscription/SubscriptionStateMachine.php` — new class
- `app/Services/Subscription/SubscriptionService.php` — uses state machine

**State Transitions:**
```
trialing  → active, canceled
active    → past_due, canceled, paused
past_due  → active (on payment), canceled (after 3 retries)
paused    → active (on resume), canceled
canceled  → (terminal state)
```

**Acceptance Criteria:**
- [ ] Invalid transitions throw `InvalidTransitionException`
- [ ] All transitions logged with timestamp and trigger
- [ ] Subscription model uses state machine for all status changes

---

#### 4.3 Implement Proration for Plan Changes
**Effort:** 1 day  
**What:** Calculate prorated amounts for mid-cycle upgrades/downgrades. Credit remaining value of current plan, charge difference for new plan.  
**Files to create:**
- `app/Services/Subscription/ProrationService.php`

**Acceptance Criteria:**
- [ ] Upgrade mid-cycle: charge prorated difference immediately
- [ ] Downgrade mid-cycle: credit applied to next billing cycle
- [ ] Proration calculation accurate to the day

---

#### 4.4 Add Grace Period & Payment Retry
**Effort:** 1 day  
**What:** 3-day grace period after payment failure. Retry payments with exponential backoff (1h, 4h, 24h). Send reminder notifications.  
**Files to create/modify:**
- `app/Jobs/RetryFailedPaymentJob.php` — new job with exponential backoff
- `app/Notifications/PaymentFailedNotification.php` — day 1, 2, 3 reminders
- `app/Console/Kernel.php` — schedule grace period checks

**Acceptance Criteria:**
- [ ] Users retain access for 3 days after payment failure
- [ ] Reminder notifications sent on days 1, 2, 3
- [ ] Payment retried 3 times with exponential backoff
- [ ] Subscription canceled after 3 failed retries

---

#### 4.5 Webhook Signature Verification for All Gateways
**Effort:** 0.5 day  
**What:** Ensure all payment webhook endpoints verify request signatures. Reject unsigned/invalid requests.  
**Files to modify:**
- `app/Http/Controllers/Webhooks/RazorpayWebhookController.php` — verify if not already
- `app/Http/Controllers/Webhooks/PayUWebhookController.php` — add verification
- `app/Http/Controllers/Webhooks/StripeWebhookController.php` — already implemented in 4.1

**Acceptance Criteria:**
- [ ] All 3 gateways verify webhook signatures
- [ ] Invalid signatures rejected with 403
- [ ] Verification failures logged to Sentry

---

### Phase 4 Checklist
- [ ] Stripe payments working globally
- [ ] Subscription state machine enforcing valid transitions
- [ ] Proration calculated correctly for plan changes
- [ ] Grace period active with 3 retry attempts
- [ ] All webhook signatures verified
- [ ] All 5 tasks merged

---

## Phase 5 — Testing & Security Hardening

**Duration:** 7 days  
**Goal:** Achieve 70%+ test coverage for services, harden auth security, ensure GDPR compliance.  
**Current Score:** Testing 4/10 → Target 8/10

### Tasks

#### 5.1 Create AI Service Mock Trait
**Effort:** 0.5 day  
**What:** Build a reusable test trait for deterministic AI responses. Allow preset responses for specific prompts. This unblocks all AI service testing.  
**Files to create:**
- `tests/Traits/AIServiceMock.php` — new trait

```php
trait AIServiceMock
{
    protected function mockAIResponse(string $pattern, string|array $response): void
    {
        $this->app->bind(AIService::class, function () use ($pattern, $response) {
            $mock = Mockery::mock(AIService::class);
            $mock->shouldReceive('generateText')
                ->with(Mockery::pattern($pattern), Mockery::any(), Mockery::any())
                ->andReturn(is_array($response) ? json_encode($response) : $response);
            return $mock;
        });
    }
}
```

**Acceptance Criteria:**
- [ ] AI responses deterministic in test environment
- [ ] Trait usable across all test classes

---

#### 5.2 Write Unit Tests for Core AI Services
**Effort:** 2 days  
**What:** Write comprehensive tests for the 10 most critical AI services.  
**Target Files:**

| Service | Test File | Priority |
|---------|-----------|----------|
| `AIService.php` | `tests/Unit/Services/AI/AIServiceTest.php` | P0 |
| `ResumeAIService.php` | `tests/Unit/Services/AI/ResumeAIServiceTest.php` | P0 |
| `SkillGapAnalyzerService.php` | `tests/Unit/Services/AI/SkillGapAnalyzerServiceTest.php` | P0 |
| `NegotiationStrategistService.php` | `tests/Unit/Services/AI/NegotiationStrategistServiceTest.php` | P1 |
| `InterviewQuestionGenerator.php` | `tests/Unit/Services/AI/InterviewQuestionGeneratorTest.php` | P1 |
| `CoverLetterGeneratorService.php` | `tests/Unit/Services/AI/CoverLetterGeneratorServiceTest.php` | P1 |
| `MarketIntelligenceService.php` | `tests/Unit/Services/AI/MarketIntelligenceServiceTest.php` | P2 |
| `PredictiveAnalyticsService.php` | `tests/Unit/Services/AI/Scout/PredictiveAnalyticsServiceTest.php` | P1 |
| `BiasEliminationService.php` | `tests/Unit/Services/AI/Scout/BiasEliminationServiceTest.php` | P1 |
| `EmbeddingService.php` | `tests/Unit/Services/AI/EmbeddingServiceTest.php` | P1 |

**Acceptance Criteria:**
- [ ] 80%+ coverage for `app/Services/AI/` directory
- [ ] Happy path + failure path + edge cases covered
- [ ] All tests use `AIServiceMock` trait (no real API calls)

---

#### 5.3 Write Feature Tests for API Endpoints
**Effort:** 2 days  
**What:** Integration tests for all critical API endpoints. Test auth, validation, response format, error handling.  
**Target Coverage:**

| API Group | Endpoint Count | Test Priority |
|-----------|---------------|---------------|
| Auth (login, register, 2FA) | ~15 | P0 |
| Jobs (search, view, apply) | ~20 | P0 |
| Agent (configure, activate, status) | ~10 | P0 |
| Profile (update, resume, skills) | ~15 | P1 |
| Payments (subscribe, webhook) | ~10 | P0 |
| SCOUT (analyze, predict, bias) | ~20 | P1 |
| Interview (session, questions, answer) | ~12 | P1 |

**Acceptance Criteria:**
- [ ] All P0 endpoints have at least 1 integration test
- [ ] Tests verify: status codes, JSON structure, auth requirements, validation rules
- [ ] CI pipeline runs all tests on every PR

---

#### 5.4 Security Hardening
**Effort:** 1 day  

**5.4a — Rate Limit Auth Endpoints:**
- Login: 5 attempts/minute
- Register: 3 attempts/minute  
- Password reset: 5 attempts/hour
- Files: `app/Http/Kernel.php`, `routes/auth.php`

**5.4b — Session Security:**
- 8-hour session timeout
- Session ID regeneration on privilege escalation
- Optional single-session enforcement
- Files: `config/session.php`, `app/Http/Middleware/`

**5.4c — Security Headers Audit:**
- Review CSP, HSTS, X-Frame-Options, X-Content-Type-Options
- Target A+ score on securityheaders.com
- Files: `app/Http/Middleware/SecurityHeaders.php`, `app/Http/Middleware/ContentSecurityPolicy.php`

**Acceptance Criteria:**
- [ ] Auth endpoints rate limited correctly
- [ ] Sessions expire after 8 hours
- [ ] Security headers score A+ on securityheaders.com

---

#### 5.5 GDPR Compliance
**Effort:** 1.5 days  

**5.5a — PII Encryption at Rest:**
- Audit all models for PII fields (phone, address, salary)
- Add `encrypted` cast to sensitive columns
- Files: Models with PII fields

**5.5b — Data Export:**
- `User::exportData()` — export all user data as JSON
- Include: profile, applications, resumes, assessments, agent activity
- Files: `app/Models/User.php`, `app/Http/Controllers/GDPRController.php`

**5.5c — Data Deletion/Anonymization:**
- `User::anonymize()` — replace PII with "REDACTED"
- Cascade to related records
- Maintain aggregate data for analytics
- Files: `app/Models/User.php`, `app/Services/GDPRService.php`

**Acceptance Criteria:**
- [ ] All PII fields encrypted at rest
- [ ] `GET /api/user/export-data` returns complete JSON export
- [ ] `DELETE /api/user/delete-account` anonymizes all PII
- [ ] Anonymized users don't appear in search results but aggregates preserved

---

### Phase 5 Checklist
- [ ] AI mock trait available for all tests
- [ ] 80%+ coverage for AI services
- [ ] All P0 API endpoints have integration tests
- [ ] Auth rate limiting active
- [ ] Session security hardened
- [ ] GDPR export and delete functional
- [ ] All 5 tasks merged

---

## Phase 6 — Event Architecture, Documentation & Polish

**Duration:** 5 days  
**Goal:** Implement true event-driven architecture, accurate documentation, and production-ready ops.  
**Current Score:** Events 4/10, Docs 7/10 → Target 9/10 each

### Tasks

#### 6.1 Create 15+ Domain Events
**Effort:** 1 day  
**What:** Create proper event classes for all major domain actions.

**Events to Create:**
```
User Lifecycle:          Payment:                  Agent:
├── UserRegistered       ├── PaymentInitiated      ├── AgentActivated
├── ProfileCompleted     ├── PaymentSucceeded      ├── AgentDeactivated
├── ResumeUploaded       ├── PaymentFailed         ├── AgentJobDiscovered
└── ResumeAnalyzed       ├── SubscriptionActivated └── AgentApplicationSubmitted
                         └── SubscriptionCanceled
Interview:               SCOUT:                    Learning:
├── InterviewStarted     ├── CandidateShortlisted  ├── SkillGapIdentified
└── InterviewCompleted   ├── PredictionGenerated   ├── LearningPathStarted
                         └── BiasAuditCompleted    └── LearningPathCompleted
```

**Files to create:** 15+ files in `app/Events/`

**Acceptance Criteria:**
- [ ] All events extend `Illuminate\Foundation\Events\Event`
- [ ] Events implement `ShouldBroadcast` where applicable (real-time UI)
- [ ] Events carry minimal payload (IDs, not full models)

---

#### 6.2 Migrate Inline Side Effects to Listeners
**Effort:** 2 days  
**What:** Audit all controllers and services. Move inline side effects (emails, points, logging, notifications) to event listeners.

**Common patterns to refactor:**

| Current (Inline) | Refactored (Event + Listener) |
|---|---|
| Controller sends email after registration | `UserRegistered` → `SendWelcomeEmail` listener |
| Controller awards points after application | `ApplicationSubmitted` → `AwardGamificationPoints` listener |
| Service logs payment after success | `PaymentSucceeded` → `LogPaymentActivity` listener |
| Controller sends notification after status change | Already done (keep as-is) |

**Files to modify:** Controllers and services throughout `app/Http/Controllers/`, `app/Services/`  
**Files to create:** 15+ listener files in `app/Listeners/`  
**Files to modify:** `app/Providers/EventServiceProvider.php` — register all event-listener mappings

**Acceptance Criteria:**
- [ ] No controller/service directly sends emails or awards points
- [ ] All side effects triggered via event dispatch
- [ ] Existing tests still pass after refactor

---

#### 6.3 Add SCOUT Decision Traceability
**Effort:** 0.5 day  
**What:** Store `explanation_json` for all SCOUT predictions. Include input features, weights, decision factors to support compliance audits.  
**Files to modify:**
- `app/Services/AI/Scout/PredictiveAnalyticsService.php` — store explanation with each prediction
- Possibly add `explanation_json` column to relevant SCOUT tables

**Acceptance Criteria:**
- [ ] Every SCOUT prediction stores explainable "why" JSON
- [ ] Explanation includes: input features used, relative weights, decision factors
- [ ] Retrievable via API for compliance audits

---

#### 6.4 Implement Model Drift Monitoring
**Effort:** 0.5 day  
**What:** Scheduled job that compares recent AI outputs against golden tests. Alert if quality degrades beyond threshold.  
**Files to create:**
- `app/Jobs/MonitorModelDriftJob.php` — new scheduled job
- `app/Console/Kernel.php` — schedule daily

**Acceptance Criteria:**
- [ ] Job runs daily comparing last 24h AI outputs to golden baselines
- [ ] Alert sent if drift > configurable threshold (default 15%)
- [ ] Drift metrics logged for trend analysis

---

#### 6.5 Update CLAUDE.md with Accurate Data
**Effort:** 0.5 day  
**What:** Apply all corrections from the CLAUDE.md Patch document. Update model counts, add warnings, tag sections as `[Authoritative]` or `[Conceptual]`.

**Key corrections:**
- Models: 290+ → 227
- Services: 80+ → 108
- Controllers: 40+ → 76
- Jobs: 20+ → 37
- Migrations: 40+ → 71
- Add agent scraper warnings
- Mark Meilisearch as INACTIVE until Phase 2 completes
- Add enterprise infrastructure section

**Acceptance Criteria:**
- [ ] All metrics match actual codebase counts
- [ ] Scrapers documented as DEMO stubs
- [ ] Sections tagged `[Authoritative]` (verified) or `[Conceptual]` (planned)

---

#### 6.6 Generate OpenAPI Documentation
**Effort:** 0.5 day  
**What:** Install `l5-swagger`. Annotate API controllers. Generate and serve OpenAPI spec.  
**Files to create/modify:**
- `composer.json` — add `darkaonline/l5-swagger`
- `config/l5-swagger.php` — configuration
- API controllers — add Swagger annotations (can be done incrementally)

**Acceptance Criteria:**
- [ ] OpenAPI spec generated at `/api/documentation`
- [ ] At least all P0 endpoints documented
- [ ] Interactive Swagger UI accessible

---

#### 6.7 Create Operations Runbook
**Effort:** 0.5 day  
**What:** Document operational procedures for production management.  
**File to create:** `docs/RUNBOOK.md`

**Sections:**
1. Deployment procedures (zero-downtime)
2. Rollback process
3. Scaling guides (horizontal, queue workers)
4. Common troubleshooting (queue stuck, AI timeout, payment failures)
5. Emergency procedures (agent kill-all, payment freeze)
6. Monitoring dashboards (Sentry, Horizon, health checks)
7. Backup and disaster recovery (RTO/RPO targets)

**Acceptance Criteria:**
- [ ] Runbook covers all operational scenarios
- [ ] New team member can perform emergency procedures using runbook alone

---

### Phase 6 Checklist
- [ ] 15+ domain events created and dispatched
- [ ] Inline side effects refactored to listeners
- [ ] SCOUT decision traceability implemented
- [ ] Model drift monitoring running daily
- [ ] CLAUDE.md accurate and tagged
- [ ] OpenAPI documentation accessible
- [ ] Operations runbook complete
- [ ] All 7 tasks merged

---

## Post-Launch Ongoing Operations

After completing all 6 phases, establish these recurring processes:

| Cadence | Activity | Owner |
|---------|----------|-------|
| **Daily** | Review Sentry errors, check Horizon queue health | On-call engineer |
| **Weekly** | Run AI golden test suite, review drift metrics | AI/ML lead |
| **Bi-weekly** | Review agent audit logs for anomalies | Product + Engineering |
| **Monthly** | Security vulnerability scan (automated) | DevOps |
| **Quarterly** | External penetration test | Security team |
| **Quarterly** | Review and update prompt versions | AI/ML lead |
| **Per release** | Run full test suite, golden tests, smoke tests | QA |

---

## Dependency Graph (Across All Phases)

```
Phase 1 (Observability)
├── 1.2 Correlation IDs ──► 1.3 Structured Logging
├── 1.4 Horizon ──► 1.7 Dead-Letter Queue
└── 1.5 Circuit Breaker (standalone)

Phase 2 (Search & AI)
├── 2.1 Meilisearch ──► 2.4 Hybrid Search
├── 2.2 Embeddings Migration ──► 2.3 Embedding Job ──► 2.4 Hybrid Search
├── 2.5 Prompt Registry ──► 2.6 Golden Tests ──► Phase 6.4 Drift Monitoring
└── 2.5 Prompt Registry ──► 2.7 Filament Admin

Phase 3 (Agent Safety) — can start partially in parallel with Phase 2
├── 3.1 Kill-Switch (standalone)
├── 3.2 Human-in-the-Loop (standalone)
├── 3.4 Audit Log ──► 3.6 Source Quality Scoring
└── 3.5 API Adapters (standalone, long lead time for API partnerships)

Phase 4 (Payments) — can start partially in parallel with Phase 3
├── 4.1 Stripe Install ──► 4.2 State Machine ──► 4.3 Proration
└── 4.2 State Machine ──► 4.4 Grace Period + Retry

Phase 5 (Testing) — depends on Phase 1-4 being mostly complete
├── 5.1 AI Mock Trait ──► 5.2 AI Service Tests
└── 5.3 API Tests (standalone)

Phase 6 (Events & Docs) — depends on all previous phases
├── 6.1 Domain Events ──► 6.2 Listener Migration
├── 2.6 Golden Tests ──► 6.4 Drift Monitoring
└── 6.5 CLAUDE.md Update (after all implementations)
```

---

## Risk Register

| ID | Risk | Probability | Impact | Mitigation |
|----|------|-------------|--------|------------|
| R1 | LinkedIn/Indeed API partnership delayed | MEDIUM | HIGH | Prioritize RSS feeds first (no API key required) |
| R2 | Vector search performance issues on MySQL | MEDIUM | MEDIUM | Use JSON storage + PHP calculation for MVP; migrate to PostgreSQL+pgvector later |
| R3 | Stripe account approval delayed | LOW | MEDIUM | Razorpay remains primary; Stripe is additive |
| R4 | Test refactoring breaks existing functionality | MEDIUM | MEDIUM | Run full test suite after each change; use feature flags |
| R5 | Event migration introduces regressions | MEDIUM | HIGH | Migrate one event at a time; keep inline code until listener verified |
| R6 | Team capacity constraints | MEDIUM | MEDIUM | Prioritize P0 items; defer P2 to post-launch |
| R7 | AI prompt migration breaks existing outputs | LOW | HIGH | Run golden tests before/after each prompt migration |

---

## Final Scorecard Target

| Domain | Current | After Phase 1 | After Phase 3 | After Phase 6 |
|--------|---------|---------------|---------------|---------------|
| Auth/Security | 8/10 | 8/10 | 8/10 | 9/10 |
| Payments/Subs | 7/10 | 7/10 | 7/10 | 9/10 |
| AI Layer | 8/10 | 8/10 | 8/10 | 9/10 |
| Job Matching/Search | 6/10 | 6/10 | 8/10 | 9/10 |
| Agent (Auto-Apply) | 5/10 | 5/10 | 8/10 | 8/10 |
| S.C.O.U.T. Employer | 8/10 | 8/10 | 8/10 | 9/10 |
| Data Model | 9/10 | 9/10 | 9/10 | 10/10 |
| APIs | 9/10 | 9/10 | 9/10 | 10/10 |
| Queues/Events | 4/10 | 6/10 | 6/10 | 9/10 |
| Observability | 3/10 | 8/10 | 8/10 | 9/10 |
| Testing | 4/10 | 4/10 | 4/10 | 8/10 |
| Docs Accuracy | 7/10 | 7/10 | 7/10 | 9/10 |
| **Overall** | **70-75%** | **78%** | **84%** | **95%+** |

---

## Summary

This roadmap transforms StudAI Career from a strong prototype to a production-grade enterprise platform in 6 phases over 6-8 weeks. The sequencing ensures:

1. **Phase 1** gives you eyes (observability) before you touch anything else
2. **Phase 2** makes the core UX work (search) and safeguards AI quality
3. **Phase 3** addresses the highest legal/reputational risk (agent safety)
4. **Phase 4** enables global revenue (Stripe + subscription management)
5. **Phase 5** builds confidence in the code (testing + security)
6. **Phase 6** cleans up architecture and documentation for long-term maintainability

**Total effort:** ~39 task-days across 38 tasks  
**Recommended team:** 2 backend engineers + 1 DevOps + 1 QA  
**Target completion:** 6-8 weeks from start

---

*Document generated from comprehensive analysis of: 01-COMPLETENESS_SCORECARD.md, 02-REALITY_VS_CLAIMS.md, 03-SYSTEM_BLUEPRINT.md, 04-IMPLEMENTATION_BACKLOG.md, 05-CLAUDE_MD_PATCH.md*
