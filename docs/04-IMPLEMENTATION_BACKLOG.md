# StudAI Career - Implementation Backlog (6-Week Sprint Plan)

> **Plan Version:** 1.0
> **Created:** February 2026
> **Objective:** Achieve enterprise production readiness

---

## Executive Summary

This backlog addresses the 20 critical gaps identified in the Completeness Scorecard. The plan is organized into 6 weekly sprints, prioritized by risk and impact.

**Team Composition Required:**
- 2 Backend Engineers
- 1 DevOps Engineer
- 1 QA Engineer
- 0.5 Security Specialist (shared)

**Total Tickets:** 60
**Estimated Story Points:** 180

---

## Sprint Overview

| Week | Theme | Focus Areas | Key Deliverables |
|------|-------|-------------|------------------|
| 1 | Foundation Hardening | Observability, Error Tracking | Sentry, Horizon, Correlation IDs |
| 2 | Vector Search & AI Governance | Semantic Search, Prompt Management | pgvector, Prompt Registry |
| 3 | Agent Safety | Kill-switch, Real Data Sources | API Adapters, Audit Logs |
| 4 | Payment Hardening | Stripe, Subscription State Machine | Global Payments, Proration |
| 5 | Testing & Security | Test Coverage, Security Audit | 70% Coverage, Penetration Test |
| 6 | Event Architecture | True Event-Driven, Documentation | 15+ Events, Updated Docs |

---

## Week 1: Foundation Hardening & Observability

### Goal
Establish production-grade observability and error handling infrastructure.

### Tickets

| ID | Title | Description | Owner | Risk | Points | Dependencies |
|----|-------|-------------|-------|------|--------|--------------|
| W1-01 | Install Sentry Error Tracking | Install and configure `sentry/sentry-laravel` package. Configure DSN, environment tagging, and user context. Test error capture. | DevOps | LOW | 3 | None |
| W1-02 | Implement Correlation ID Middleware | Create `CorrelationIdMiddleware` that generates UUID per request, stores in request context, and adds to response headers. | Backend | LOW | 3 | None |
| W1-03 | Add Structured JSON Logging | Configure Laravel logging to use JSON format. Include correlation_id, user_id, request_uri, and timestamp in all log entries. | Backend | LOW | 3 | W1-02 |
| W1-04 | Install Laravel Horizon | Install `laravel/horizon`, configure queues, set up authentication middleware, and create monitoring dashboard access. | DevOps | LOW | 5 | Redis |
| W1-05 | Implement Circuit Breaker for AI Services | Create `CircuitBreaker` class wrapping AI calls. Track failures, open circuit after 3 consecutive failures, auto-reset after 60s. | Backend | MEDIUM | 5 | None |
| W1-06 | Add Idempotency Key Middleware | Create `IdempotencyMiddleware` for payment endpoints. Store idempotency key → response mapping in Redis. Reject duplicate requests. | Backend | MEDIUM | 5 | Redis |
| W1-07 | Create Dead-Letter Queue Strategy | Configure failed_jobs table retention, create `ProcessFailedJobs` command, set up Slack/email alerting for failed jobs. | DevOps | LOW | 3 | W1-04 |
| W1-08 | Add Health Check Endpoints | Create `/health` (basic) and `/ready` (with DB/Redis/Queue checks) endpoints. Configure load balancer health checks. | Backend | LOW | 2 | None |

### Acceptance Criteria

- [ ] All unhandled exceptions captured in Sentry with full stack traces
- [ ] Every log line includes `correlation_id` field
- [ ] Horizon dashboard accessible at `/horizon` (admin only)
- [ ] AI service failures trigger circuit breaker after 3 failures
- [ ] Duplicate payment webhooks return cached response (no duplicate processing)
- [ ] Health endpoints return 200 when services are up

### Week 1 Definition of Done
- All 8 tickets merged to main branch
- CI pipeline passes
- Staging deployment successful
- Manual QA verification complete

---

## Week 2: Vector Search & AI Governance

### Goal
Enable semantic search capability and establish AI quality infrastructure.

### Tickets

| ID | Title | Description | Owner | Risk | Points | Dependencies |
|----|-------|-------------|-------|------|--------|--------------|
| W2-01 | Install pgvector Extension | Install pgvector extension on PostgreSQL. Update DATABASE_URL for vector support. Create test to verify extension works. | DevOps | MEDIUM | 3 | PostgreSQL |
| W2-02 | Create job_embeddings Migration | Create migration for `job_embeddings` table with columns: job_id (FK), embedding (vector(1536)), created_at. Add indexes. | Backend | LOW | 2 | W2-01 |
| W2-03 | Implement EmbeddingService | Create `EmbeddingService` class using Azure OpenAI `text-embedding-3-large`. Methods: `generate(string)`, `generateBatch(array)`. | Backend | MEDIUM | 5 | W2-02 |
| W2-04 | Create GenerateJobEmbeddings Job | Create queued job to generate embeddings for all jobs. Process in batches of 100. Track progress. Handle failures gracefully. | Backend | LOW | 3 | W2-03 |
| W2-05 | Implement HybridSearchService | Create `HybridSearchService` combining Meilisearch keyword search with vector similarity. Use RRF (Reciprocal Rank Fusion) for merging. | Backend | HIGH | 8 | W2-03, W2-10 |
| W2-06 | Create ai_prompts Migration | Create `ai_prompts` table: id, name (unique), version, template (text), system_prompt (text), is_active, created_at. | Backend | LOW | 2 | None |
| W2-07 | Implement PromptRegistry Service | Create `PromptRegistry` service. Methods: `get(name)`, `getActive(name)`, `create(data)`, `setActive(name, version)`. Cache prompts. | Backend | MEDIUM | 5 | W2-06 |
| W2-08 | Create ai_golden_tests Migration | Create `ai_golden_tests` table: prompt_id (FK), test_name, input (JSON), expected_output (JSON), similarity_threshold, is_active. | Backend | LOW | 2 | W2-06 |
| W2-09 | Implement AIEvaluationService | Create `AIEvaluationService` with `runGoldenTests()` method. Compare AI outputs to expected outputs. Fail if below threshold. | Backend | MEDIUM | 5 | W2-08 |
| W2-10 | Enable Meilisearch Driver | Update `config/scout.php` driver to `meilisearch`. Configure index settings. Run `php artisan scout:import` for Job model. | DevOps | LOW | 2 | Meilisearch server |

### Acceptance Criteria

- [ ] Jobs searchable by semantic similarity ("ML role" finds "Machine Learning Engineer")
- [ ] Vector search performs < 100ms for 10,000 jobs
- [ ] All AI prompts stored in database with version history
- [ ] Golden test suite runs successfully
- [ ] CI fails if golden tests show regression > threshold
- [ ] Meilisearch active and synced with job_listings

### Migration: ai_prompts
```php
Schema::create('ai_prompts', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->integer('version')->default(1);
    $table->text('template');
    $table->text('system_prompt')->nullable();
    $table->boolean('is_active')->default(true);
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->unique(['name', 'version']);
});
```

---

## Week 3: Agent Safety & Real Data Sources

### Goal
Make the autonomous agent production-safe with real job data sources.

### Tickets

| ID | Title | Description | Owner | Risk | Points | Dependencies |
|----|-------|-------------|-------|------|--------|--------------|
| W3-01 | Implement require_approval Gate | Add check in `ProcessAutoApplications` job to respect `require_approval` flag. Create pending applications for approval. | Backend | LOW | 3 | None |
| W3-02 | Add Agent Kill-Switch | Add `emergency_stop` column to `agent_configurations`. Create middleware to check before any agent action. | Backend | LOW | 3 | Migration |
| W3-03 | Create Admin Kill-All Endpoint | Create `POST /admin/agent/kill-all` endpoint. Sets `emergency_stop=true` for all agents. Requires admin role. | Backend | LOW | 2 | W3-02 |
| W3-04 | Implement Daily Cap Enforcement | Add hard limit check in `ProcessAutoApplications`. Track daily count in `agent_configurations.applications_today`. Reset at midnight. | Backend | LOW | 3 | None |
| W3-05 | Create Agent Audit Log Model | Create `AgentAuditLog` model and migration. Fields: user_id, action, job_id, result, metadata (JSON), correlation_id, created_at. | Backend | MEDIUM | 5 | None |
| W3-06 | Replace LinkedIn Scraper with API | Implement `LinkedInPartnerAPIService` using LinkedIn official API. Requires Partner Program access. Fall back gracefully if unavailable. | Backend | HIGH | 8 | API Partnership |
| W3-07 | Implement Indeed Publisher API | Create `IndeedPublisherService` using Indeed Publisher API. Parse XML feed. Map to `DiscoveredJob` model. | Backend | HIGH | 8 | API Key |
| W3-08 | Add RSS/Atom Feed Aggregator | Create `RSSJobFeedService` to aggregate jobs from public RSS feeds (RemoteOK, WeWorkRemotely, etc.). Schedule hourly refresh. | Backend | MEDIUM | 5 | None |
| W3-09 | Implement Job Source Quality Scoring | Track successful applications per source. Calculate `quality_score` for each `JobSource`. Prioritize high-quality sources in matching. | Backend | MEDIUM | 5 | W3-05 |
| W3-10 | Enforce Company Blacklist | Add blacklist check in `JobMatchingService.calculateMatch()`. Return score=0 for blacklisted companies. Store reason in audit log. | Backend | LOW | 2 | None |

### Acceptance Criteria

- [ ] Users can enable "require approval before apply"
- [ ] Admin can disable all agents with single API call
- [ ] Daily cap enforced even if job runs multiple times
- [ ] Every agent action logged with full context
- [ ] At least 2 real job sources providing actual listings
- [ ] Blacklisted companies never receive auto-applications

### Agent Audit Log Migration
```php
Schema::create('agent_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('action'); // discover, match, apply, skip, error
    $table->foreignId('discovered_job_id')->nullable()->constrained()->onDelete('set null');
    $table->string('result'); // success, failed, skipped, pending
    $table->json('metadata')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
    $table->index(['action', 'result']);
});
```

---

## Week 4: Stripe Integration & Payment Hardening

### Goal
Enable global payment support with proper subscription lifecycle management.

### Tickets

| ID | Title | Description | Owner | Risk | Points | Dependencies |
|----|-------|-------------|-------|------|--------|--------------|
| W4-01 | Install Stripe PHP Package | Install `stripe/stripe-php`. Configure Stripe API keys in `.env`. Create `config/stripe.php`. | Backend | LOW | 2 | Stripe Account |
| W4-02 | Add Stripe Configuration | Update `config/payment.php` to include Stripe settings alongside Razorpay/PayU. Add gateway selector logic. | Backend | LOW | 2 | W4-01 |
| W4-03 | Implement StripeGatewayService | Create `StripeGatewayService` with methods: `createCheckoutSession()`, `createSubscription()`, `cancelSubscription()`, `handleWebhook()`. | Backend | MEDIUM | 8 | W4-02 |
| W4-04 | Create Stripe Webhook Endpoint | Create `POST /webhooks/stripe` endpoint. Handle events: checkout.session.completed, invoice.paid, invoice.payment_failed, customer.subscription.updated. | Backend | MEDIUM | 5 | W4-03 |
| W4-05 | Implement Webhook Signature Verification | Add signature verification for all payment webhooks. Reject requests with invalid signatures. Log verification failures to Sentry. | Backend | MEDIUM | 3 | None |
| W4-06 | Create SubscriptionStateMachine | Create `SubscriptionStateMachine` class. States: trialing, active, past_due, canceled, paused. Transitions: activate(), suspend(), cancel(), pause(), resume(). | Backend | MEDIUM | 5 | None |
| W4-07 | Implement Proration Logic | Create `ProrationService` for plan changes. Calculate prorated amount for upgrades/downgrades. Handle mid-cycle changes correctly. | Backend | HIGH | 8 | W4-06 |
| W4-08 | Add Grace Period Handling | Implement 3-day grace period after payment failure before suspending access. Send reminder notifications at days 1, 2, 3. | Backend | MEDIUM | 5 | W4-06 |
| W4-09 | Create Subscription Status Changed Event | Create `SubscriptionStatusChanged` event. Dispatch on all state transitions. Add listener for webhook delivery to external systems. | Backend | LOW | 3 | W4-06 |
| W4-10 | Implement Payment Retry Job | Create `RetryFailedPayment` job with exponential backoff (1h, 4h, 24h). Mark subscription as canceled after 3 failures. | Backend | MEDIUM | 5 | W4-06 |

### Acceptance Criteria

- [ ] Users can pay via Stripe (card globally)
- [ ] Subscription transitions through proper state machine
- [ ] Upgrading mid-cycle calculates prorated charge correctly
- [ ] Failed payments retry 3x with exponential backoff
- [ ] All webhook signatures verified before processing
- [ ] Grace period notifications sent on days 1, 2, 3

### Subscription State Machine
```php
class SubscriptionStateMachine
{
    const STATE_TRIALING = 'trialing';
    const STATE_ACTIVE = 'active';
    const STATE_PAST_DUE = 'past_due';
    const STATE_CANCELED = 'canceled';
    const STATE_PAUSED = 'paused';

    const TRANSITIONS = [
        'trialing' => ['active', 'canceled'],
        'active' => ['past_due', 'canceled', 'paused'],
        'past_due' => ['active', 'canceled'],
        'paused' => ['active', 'canceled'],
        'canceled' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? []);
    }
}
```

---

## Week 5: Testing & Security Hardening

### Goal
Achieve comprehensive test coverage and pass security audit.

### Tickets

| ID | Title | Description | Owner | Risk | Points | Dependencies |
|----|-------|-------------|-------|------|--------|--------------|
| W5-01 | Unit Tests for AI Services | Write unit tests for all AI services using mocked OpenAI responses. Target 80% coverage for `app/Services/AI/`. | QA | MEDIUM | 8 | None |
| W5-02 | Feature Tests for API Endpoints | Write feature tests for all API endpoints in `routes/api.php`. Test auth, validation, and response formats. | QA | MEDIUM | 8 | None |
| W5-03 | Implement AI Mocking Helper | Create `AIServiceMock` trait for deterministic AI responses in tests. Allow preset responses for specific prompts. | QA | MEDIUM | 5 | None |
| W5-04 | Security Headers Audit | Review and update `ContentSecurityPolicy` and `SecurityHeaders` middleware. Achieve A+ score on securityheaders.com. | Security | LOW | 3 | None |
| W5-05 | Rate Limit Auth Endpoints | Add stricter rate limiting: 5/min for login, 3/min for register, 5/hour for password reset. Use Redis for counters. | Backend | LOW | 3 | None |
| W5-06 | Session Security Hardening | Implement 8-hour session timeout. Add optional single-session enforcement. Regenerate session ID on privilege escalation. | Backend | LOW | 3 | None |
| W5-07 | PII Encryption Audit | Audit all models with PII. Ensure encryption at rest for: phone, address, SSN (if any), salary info. Use Laravel's `encrypted` cast. | Security | MEDIUM | 5 | None |
| W5-08 | Implement GDPR Data Export | Create `User::exportData()` method. Export all user data as JSON. Include applications, resumes, assessments. Redact sensitive keys. | Backend | MEDIUM | 5 | None |
| W5-09 | Implement GDPR Delete/Anonymize | Create `User::anonymize()` method. Replace PII with "REDACTED". Cascade to related records. Maintain aggregate data for analytics. | Backend | MEDIUM | 5 | None |
| W5-10 | External Security Penetration Test | Engage third-party security firm for penetration test. Fix all critical/high findings before production. | Security | HIGH | 8 | External |

### Acceptance Criteria

- [ ] Test coverage >= 70% for services, >= 50% overall
- [ ] All API endpoints have at least one integration test
- [ ] Security headers score A+ on securityheaders.com
- [ ] Auth endpoints rate limited correctly
- [ ] GDPR export produces valid JSON with all user data
- [ ] GDPR delete anonymizes PII without breaking relations
- [ ] Penetration test completed with no critical findings

### AI Mocking Example
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

---

## Week 6: Event Architecture & Documentation

### Goal
Establish true event-driven architecture and ensure documentation accuracy.

### Tickets

| ID | Title | Description | Owner | Risk | Points | Dependencies |
|----|-------|-------------|-------|------|--------|--------------|
| W6-01 | Create Domain Events (15+) | Create event classes for all major domain actions: UserRegistered, ProfileCompleted, PaymentSucceeded, etc. (see list below) | Backend | MEDIUM | 5 | None |
| W6-02 | Migrate Inline Side Effects to Listeners | Audit controllers/services. Move email sending, points awarding, logging to event listeners. Remove inline side effects. | Backend | HIGH | 8 | W6-01 |
| W6-03 | Implement Application Event Sourcing | Create `ApplicationEventStore` to store all application state changes. Enable replay of application history. | Backend | HIGH | 8 | W6-01 |
| W6-04 | Add Decision Traceability for SCOUT | Store `explanation_json` for all SCOUT predictions. Include input features, weights, and decision factors. | Backend | MEDIUM | 5 | None |
| W6-05 | Create Model Drift Monitoring Job | Create scheduled job to compare recent AI outputs against golden tests. Alert if drift exceeds threshold. | Backend | MEDIUM | 5 | W2-09 |
| W6-06 | Update CLAUDE.md with Accurate Counts | Update all metric counts in CLAUDE.md. Add [Authoritative] and [Conceptual] tags. Mark scrapers as demo. | Docs | LOW | 2 | None |
| W6-07 | Generate OpenAPI Documentation | Install `l5-swagger`. Annotate all API controllers. Generate OpenAPI spec. Serve at `/api/documentation`. | Backend | LOW | 5 | None |
| W6-08 | Create Operations Runbook | Document common operational tasks: scaling, monitoring, debugging, deployment rollback. Store in `docs/RUNBOOK.md`. | DevOps | LOW | 3 | None |
| W6-09 | Document Disaster Recovery Procedures | Define backup strategy, RTO/RPO targets, failover procedures. Document database recovery process. | DevOps | LOW | 3 | None |
| W6-10 | Create Architecture Decision Records | Document major architectural decisions: AI provider choice, payment gateway selection, search strategy. Use ADR format. | Arch | LOW | 3 | None |

### New Events to Create (W6-01)

```php
// User Lifecycle
App\Events\UserRegistered
App\Events\ProfileCompleted
App\Events\ResumeUploaded
App\Events\ResumeAnalyzed

// Interview
App\Events\InterviewSessionStarted
App\Events\InterviewSessionCompleted

// Payments
App\Events\PaymentInitiated
App\Events\PaymentSucceeded
App\Events\PaymentFailed
App\Events\SubscriptionActivated
App\Events\SubscriptionCanceled

// Agent
App\Events\AgentActivated
App\Events\AgentDeactivated
App\Events\AgentJobDiscovered
App\Events\AgentApplicationSubmitted

// SCOUT
App\Events\CandidateShortlisted
App\Events\PredictionGenerated
App\Events\BiasAuditCompleted

// Learning
App\Events\SkillGapIdentified
App\Events\LearningPathStarted
App\Events\LearningPathCompleted
```

### Acceptance Criteria

- [ ] All major domain actions dispatch events
- [ ] Side effects handled through listeners, not inline code
- [ ] Application status changes create event store entries
- [ ] SCOUT predictions include explainable "why" JSON
- [ ] Model drift alerts sent when degradation detected
- [ ] CLAUDE.md accurately reflects codebase reality
- [ ] OpenAPI spec generated and accessible

---

## Risk Register

| ID | Risk | Probability | Impact | Mitigation |
|----|------|-------------|--------|------------|
| R1 | LinkedIn API partnership delayed | MEDIUM | HIGH | Prioritize RSS feeds and Indeed API as alternatives |
| R2 | Vector search performance issues | LOW | MEDIUM | Benchmark early, consider Qdrant if pgvector slow |
| R3 | Stripe account approval delayed | LOW | MEDIUM | Keep Razorpay as primary, Stripe as optional |
| R4 | External pentest finds critical issues | MEDIUM | HIGH | Plan buffer time in Week 5 for remediation |
| R5 | Existing tests fail after event refactor | MEDIUM | MEDIUM | Run full test suite after each event migration |
| R6 | Team capacity constraints | MEDIUM | MEDIUM | Prioritize P0 tickets, defer P1 to post-launch |

---

## Dependencies Matrix

```
W1-02 ──► W1-03 (Correlation IDs before structured logging)
W1-04 ──► W1-07 (Horizon before dead-letter monitoring)
W2-01 ──► W2-02 ──► W2-03 ──► W2-04/W2-05 (Vector chain)
W2-06 ──► W2-07/W2-08 ──► W2-09 (Prompt registry chain)
W3-02 ──► W3-03 (Kill-switch before admin endpoint)
W2-09 ──► W6-05 (Golden tests before drift monitoring)
W4-01 ──► W4-02 ──► W4-03 ──► W4-04 (Stripe chain)
W4-06 ──► W4-07/W4-08/W4-10 (State machine before features)
W6-01 ──► W6-02/W6-03 (Events before refactoring)
```

---

## Definition of Done (Global)

All tickets must satisfy:

1. **Code Complete**: Implementation merged to main branch
2. **Tests Passing**: All existing tests pass; new tests written for new code
3. **Code Review**: PR approved by at least one other engineer
4. **Documentation**: README/inline comments updated if applicable
5. **Staging Verified**: Feature works correctly in staging environment
6. **Security Review**: No new vulnerabilities introduced (for security-related tickets)

---

## Post-Sprint Maintenance

After Week 6, establish ongoing processes:

1. **Weekly AI Evaluation**: Run golden tests, review drift metrics
2. **Monthly Security Scans**: Automated vulnerability scanning
3. **Quarterly Penetration Tests**: External security reviews
4. **Continuous Monitoring**: Sentry alerts, Horizon queue health, uptime monitoring

---

## Conclusion

Completing this 6-week backlog will transform StudAI Career from a 70-75% complete prototype to an enterprise-ready platform. The prioritization ensures critical safety and observability gaps are addressed first, followed by feature completeness and quality assurance.

**Estimated Total Effort:** 180 story points / 6 weeks / 4 engineers = 7.5 points/engineer/week

This is achievable with focused execution and minimal scope creep.
