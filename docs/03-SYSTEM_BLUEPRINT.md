# StudAI Career - System Blueprint

> **Version:** 1.0
> **Last Updated:** February 2026
> **Purpose:** Complete runtime architecture and workflow documentation

---

## Table of Contents

1. [High-Level Architecture](#1-high-level-architecture)
2. [Request Lifecycle](#2-request-lifecycle)
3. [Authentication Flows](#3-authentication-flows)
4. [Core User Workflows](#4-core-user-workflows)
5. [AI Integration Layer](#5-ai-integration-layer)
6. [Payment Processing](#6-payment-processing)
7. [Event-Driven Processing](#7-event-driven-processing)
8. [Background Job System](#8-background-job-system)
9. [Data Flow Diagrams](#9-data-flow-diagrams)

---

## 1. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CLIENT LAYER                                   │
├─────────────────┬─────────────────┬─────────────────┬──────────────────┤
│   Web App       │   PWA/Mobile    │   API Clients   │   Filament Admin │
│   (Blade/       │   (Service      │   (Third-party  │   (Admin Panel)  │
│   Livewire/     │   Worker)       │   Integrations) │                  │
│   Alpine.js)    │                 │                 │                  │
└────────┬────────┴────────┬────────┴────────┬────────┴────────┬─────────┘
         │                 │                 │                 │
         ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        APPLICATION LAYER                                 │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │                     Laravel 11/12 Framework                        │  │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐  │  │
│  │  │ Controllers │ │  Livewire   │ │  Middleware │ │   Events    │  │  │
│  │  │   (76)      │ │  (24)       │ │   (14)      │ │    (2)      │  │  │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘  │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
         │                 │                 │                 │
         ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         SERVICE LAYER                                    │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────────┐   │
│  │  AI (50+)   │ │  Agent (11) │ │  Scout (14) │ │   Core (30+)    │   │
│  │  Services   │ │  Services   │ │  Services   │ │   Services      │   │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
         │                 │                 │                 │
         ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        EXTERNAL SERVICES                                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────────┐   │
│  │ Azure       │ │ Razorpay    │ │ Meilisearch │ │  Background     │   │
│  │ OpenAI      │ │ PayU        │ │ (planned)   │ │  Check APIs     │   │
│  │ Anthropic   │ │             │ │             │ │                 │   │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
         │                 │                 │                 │
         ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          DATA LAYER                                      │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────────┐   │
│  │   MySQL     │ │   Redis     │ │   Queue     │ │   File Storage  │   │
│  │   (227      │ │   (Cache)   │ │   (Jobs)    │ │   (Uploads)     │   │
│  │   Models)   │ │             │ │             │ │                 │   │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Request Lifecycle

### Web Request Flow

```
HTTP Request
     │
     ▼
┌─────────────────────────────────────────┐
│         Nginx / Apache                   │
│         (Web Server)                     │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      public/index.php                    │
│      (Laravel Entry Point)               │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      bootstrap/app.php                   │
│      ├── Exception Handler               │
│      ├── Console Kernel                  │
│      └── HTTP Kernel                     │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      Middleware Pipeline                 │
│      ┌─────────────────────────────────┐│
│      │ 1. SecurityHeaders              ││
│      │ 2. ContentSecurityPolicy        ││
│      │ 3. EncryptCookies               ││
│      │ 4. StartSession                 ││
│      │ 5. VerifyCsrfToken              ││
│      │ 6. TrackUserActivity            ││
│      │ 7. auth (if protected)          ││
│      │ 8. CheckSubscriptionStatus      ││
│      │ 9. RateLimitByPlan              ││
│      └─────────────────────────────────┘│
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      Router (routes/web.php)             │
│      Matches URI → Controller@method     │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      Controller                          │
│      ├── Validates Input (FormRequest)   │
│      ├── Calls Service Layer             │
│      └── Returns Response (View/JSON)    │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      Service Layer                       │
│      ├── Business Logic                  │
│      ├── Model Interactions              │
│      ├── External API Calls              │
│      └── Event Dispatching               │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      Response                            │
│      ├── Blade View (HTML)               │
│      ├── Livewire Component              │
│      └── JSON (API)                      │
└─────────────────────────────────────────┘
```

### API Request Flow

```
API Request (Authorization: Bearer {token})
     │
     ▼
┌─────────────────────────────────────────┐
│      API Middleware Pipeline             │
│      ┌─────────────────────────────────┐│
│      │ 1. api middleware group         ││
│      │ 2. auth:sanctum                 ││
│      │    OR ApiTokenAuthentication    ││
│      │ 3. ApiAbilityCheck (abilities)  ││
│      │ 4. ApiRateLimiting              ││
│      │ 5. throttle (per endpoint)      ││
│      └─────────────────────────────────┘│
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│      API Controller                      │
│      └── Returns JSON Response           │
└─────────────────────────────────────────┘
```

---

## 3. Authentication Flows

### 3.1 Web Authentication (Fortify)

```
┌──────────────────────────────────────────────────────────────────┐
│                    REGISTRATION FLOW                              │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  User                  Application                    Database    │
│   │                        │                             │        │
│   │  POST /register        │                             │        │
│   │ ─────────────────────► │                             │        │
│   │                        │                             │        │
│   │                        │  Validate Input             │        │
│   │                        │  (RegisteredUserController) │        │
│   │                        │                             │        │
│   │                        │  Create User ──────────────►│        │
│   │                        │                             │        │
│   │                        │  Dispatch Registered Event  │        │
│   │                        │                             │        │
│   │                        │  Send Verification Email    │        │
│   │                        │                             │        │
│   │  ◄───────────────────  │  Redirect to Dashboard     │        │
│   │  (Unverified state)    │                             │        │
│   │                        │                             │        │
│   │  GET /verify-email/{id}│                             │        │
│   │  ─────────────────────►│                             │        │
│   │                        │                             │        │
│   │                        │  Mark email_verified_at ───►│        │
│   │                        │                             │        │
│   │  ◄───────────────────  │  Redirect to Dashboard     │        │
│   │  (Verified)            │                             │        │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘
```

### 3.2 Login with 2FA

```
┌──────────────────────────────────────────────────────────────────┐
│                    LOGIN FLOW (WITH 2FA)                          │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Step 1: Credentials                                              │
│  ───────────────────                                              │
│  POST /login                                                      │
│  { email, password }                                              │
│       │                                                           │
│       ▼                                                           │
│  ┌─────────────────────┐                                          │
│  │ Validate Credentials│                                          │
│  └──────────┬──────────┘                                          │
│             │                                                     │
│       ┌─────┴─────┐                                               │
│       │ 2FA       │                                               │
│       │ Enabled?  │                                               │
│       └─────┬─────┘                                               │
│         YES │ NO                                                  │
│             │  └──────────► Create Session ──► Dashboard          │
│             │                                                     │
│             ▼                                                     │
│  Step 2: TOTP Verification                                        │
│  ─────────────────────────                                        │
│  POST /two-factor-challenge                                       │
│  { code: "123456" }                                               │
│       │                                                           │
│       ▼                                                           │
│  ┌─────────────────────┐                                          │
│  │ TwoFactorService    │                                          │
│  │ .verify(code)       │                                          │
│  └──────────┬──────────┘                                          │
│             │                                                     │
│       ┌─────┴─────┐                                               │
│       │ Valid?    │                                               │
│       └─────┬─────┘                                               │
│         YES │ NO                                                  │
│             │  └──────────► Error: Invalid Code                   │
│             │                                                     │
│             ▼                                                     │
│  Create Session ──► Dashboard                                     │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘
```

### 3.3 API Authentication (Sanctum)

```
┌──────────────────────────────────────────────────────────────────┐
│                    API TOKEN AUTHENTICATION                       │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Option A: Personal Access Token (Job Seeker)                     │
│  ─────────────────────────────────────────────                    │
│  Authorization: Bearer {personal_access_token}                    │
│       │                                                           │
│       ▼                                                           │
│  auth:sanctum middleware                                          │
│       │                                                           │
│       ▼                                                           │
│  User authenticated via token → $request->user()                  │
│                                                                   │
│                                                                   │
│  Option B: API Token (Third-Party)                                │
│  ──────────────────────────────────                               │
│  Authorization: Bearer {api_token}                                │
│       │                                                           │
│       ▼                                                           │
│  ApiTokenAuthentication middleware                                │
│       │                                                           │
│       ├── Lookup token in api_tokens table                        │
│       ├── Check expiration                                        │
│       └── Verify abilities (company.read, jobs.write, etc.)       │
│       │                                                           │
│       ▼                                                           │
│  ApiAbilityCheck middleware                                       │
│       │                                                           │
│       ├── Gate::check('ability')                                  │
│       └── Reject if ability not granted                           │
│       │                                                           │
│       ▼                                                           │
│  ApiRateLimiting middleware                                       │
│       │                                                           │
│       └── Enforce per-token rate limits                           │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘
```

---

## 4. Core User Workflows

### 4.1 Job Seeker Registration & Onboarding

```
┌─────────────────────────────────────────────────────────────────────┐
│                 JOB SEEKER ONBOARDING FLOW                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Registration                                                     │
│     POST /register → User created → Email sent                       │
│                                                                      │
│  2. Email Verification                                               │
│     GET /verify-email/{id} → email_verified_at set                   │
│                                                                      │
│  3. Profile Wizard (Livewire: ProfileWizard)                         │
│     ┌─────────────────────────────────────────────┐                  │
│     │  Step 1: Basic Info (name, location, phone) │                  │
│     │  Step 2: Work Experience                    │                  │
│     │  Step 3: Education                          │                  │
│     │  Step 4: Skills                             │                  │
│     │  Step 5: Career Goals                       │                  │
│     └─────────────────────────────────────────────┘                  │
│                                                                      │
│  4. Resume Upload (Optional)                                         │
│     POST /profile/career/upload-resume                               │
│         │                                                            │
│         ▼                                                            │
│     ResumeAIService.extractData()                                    │
│         │                                                            │
│         ▼                                                            │
│     Auto-populate profile sections                                   │
│                                                                      │
│  5. Skill Gap Analysis                                               │
│     SkillGapAnalyzerService.analyze(user, targetRole)                │
│         │                                                            │
│         ▼                                                            │
│     Create SkillGap records                                          │
│         │                                                            │
│         ▼                                                            │
│     Generate LearningPath recommendations                            │
│                                                                      │
│  6. Dashboard Ready                                                  │
│     User → /dashboard with:                                          │
│     • Job recommendations                                            │
│     • Skill gaps                                                     │
│     • Learning paths                                                 │
│     • Application tracking                                           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.2 Job Search & Application Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    JOB SEARCH & APPLICATION                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Search Jobs                                                      │
│     GET /jobs/search?keywords=developer&location=remote              │
│         │                                                            │
│         ▼                                                            │
│     JobController@search                                             │
│         │                                                            │
│         ▼                                                            │
│     JobSearchService.search(params)                                  │
│         │                                                            │
│         ▼                                                            │
│     Scout Query (currently: collection driver)                       │
│     [FUTURE: Meilisearch + Vector hybrid]                            │
│         │                                                            │
│         ▼                                                            │
│     Return paginated results                                         │
│                                                                      │
│  2. View Job Details                                                 │
│     GET /jobs/{id}                                                   │
│         │                                                            │
│         ▼                                                            │
│     JobController@show                                               │
│         │                                                            │
│         ▼                                                            │
│     JobMatchingService.calculateMatch(user, job)                     │
│         │                                                            │
│         ├── Skills match percentage                                  │
│         ├── Experience match                                         │
│         ├── Location/remote compatibility                            │
│         └── Salary range fit                                         │
│         │                                                            │
│         ▼                                                            │
│     Display job with match score                                     │
│                                                                      │
│  3. Apply to Job                                                     │
│     POST /api/jobs/{id}/apply                                        │
│         │                                                            │
│         ▼                                                            │
│     JobMatchingController@apply                                      │
│         │                                                            │
│         ├── Create Application record                                │
│         ├── Attach resume (primary or selected)                      │
│         ├── Attach cover letter (optional AI-generated)              │
│         │                                                            │
│         ▼                                                            │
│     Dispatch ApplicationSubmitted event                              │
│         │                                                            │
│         ├── SendApplicationSubmittedNotification                     │
│         │   └── Notify employer                                      │
│         │                                                            │
│         └── GamificationEventSubscriber                              │
│             └── Award points to user                                 │
│                                                                      │
│  4. Track Application                                                │
│     GET /applications                                                │
│         │                                                            │
│         ▼                                                            │
│     DashboardController@applications                                 │
│         │                                                            │
│         ▼                                                            │
│     Display application funnel:                                      │
│     Applied → Viewed → Shortlisted → Interview → Offer               │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.3 Interview Preparation Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    INTERVIEW PREPARATION                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Create Session                                                   │
│     POST /api/interview/sessions                                     │
│     { company_id, job_id, interview_type }                           │
│         │                                                            │
│         ▼                                                            │
│     InterviewSessionController@start                                 │
│         │                                                            │
│         ▼                                                            │
│     Create InterviewSession record                                   │
│         │                                                            │
│         ▼                                                            │
│     Queue GenerateInterviewQuestionsJob                              │
│         │                                                            │
│         ▼                                                            │
│     InterviewQuestionGenerator (AIService)                           │
│         │                                                            │
│         ├── Behavioral questions                                     │
│         ├── Technical questions                                      │
│         ├── Role-specific questions                                  │
│         └── Company-specific questions                               │
│         │                                                            │
│         ▼                                                            │
│     Store InterviewQuestion records (10 questions)                   │
│                                                                      │
│  2. Answer Questions                                                 │
│     GET /api/interview/sessions/{id}/next-question                   │
│         │                                                            │
│         ▼                                                            │
│     Return next unanswered question                                  │
│                                                                      │
│     POST /api/interview/sessions/{id}/answer                         │
│     { question_id, answer_text, audio_url? }                         │
│         │                                                            │
│         ▼                                                            │
│     Store InterviewResponse                                          │
│         │                                                            │
│         ▼                                                            │
│     AnswerEvaluationService.evaluate(response)                       │
│         │                                                            │
│         ├── Content relevance score                                  │
│         ├── Structure & clarity score                                │
│         ├── Keyword coverage                                         │
│         └── Competency demonstration                                 │
│         │                                                            │
│         ▼                                                            │
│     Store InterviewFeedback                                          │
│                                                                      │
│  3. Get Report                                                       │
│     GET /api/interview/sessions/{id}/report                          │
│         │                                                            │
│         ▼                                                            │
│     InterviewSessionController@getReport                             │
│         │                                                            │
│         ▼                                                            │
│     Generate InterviewPerformanceReport                              │
│         │                                                            │
│         ├── Overall score                                            │
│         ├── Strengths identified                                     │
│         ├── Improvement areas                                        │
│         └── Specific recommendations                                 │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.4 Agent Auto-Apply Flow (Current: Demo Mode)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    AGENT AUTO-APPLY (DEMO MODE)                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ⚠️  WARNING: Scrapers return demo data only!                        │
│                                                                      │
│  1. Configure Agent                                                  │
│     POST /agent/configure                                            │
│         │                                                            │
│         ▼                                                            │
│     Create/Update AgentConfiguration                                 │
│     • target_roles: ["Software Engineer", "Developer"]               │
│     • preferred_locations: ["Remote", "NYC"]                         │
│     • match_threshold_percentage: 70                                 │
│     • daily_application_limit: 10                                    │
│     • require_approval: false  ← DANGEROUS                           │
│                                                                      │
│  2. Activate Agent                                                   │
│     POST /api/agent/activate                                         │
│         │                                                            │
│         ▼                                                            │
│     AgentConfiguration.is_active = true                              │
│                                                                      │
│  3. Job Discovery (Scheduled/Manual)                                 │
│     ProcessAutoApplications job runs                                 │
│         │                                                            │
│         ▼                                                            │
│     JobAggregationService.aggregateFromAllSources()                  │
│         │                                                            │
│         ├── LinkedInScraperService.scrape()  → DEMO DATA             │
│         ├── IndeedScraperService.scrape()    → DEMO DATA             │
│         ├── GlassdoorScraperService.scrape() → DEMO DATA             │
│         └── RSSFeedParser.parse()            → DEMO DATA             │
│         │                                                            │
│         ▼                                                            │
│     Store DiscoveredJob records                                      │
│                                                                      │
│  4. Matching & Auto-Apply                                            │
│     For each DiscoveredJob:                                          │
│         │                                                            │
│         ▼                                                            │
│     JobMatchingService.calculateMatch(user, job)                     │
│         │                                                            │
│         ▼                                                            │
│     If score >= threshold AND company not blacklisted:               │
│         │                                                            │
│         ├── [MISSING] Check require_approval                         │
│         ├── [MISSING] Human-in-the-loop gate                         │
│         │                                                            │
│         ▼                                                            │
│     AutoApplicationAgentService.apply()                              │
│         │                                                            │
│         ├── Customize resume (if enabled)                            │
│         ├── Generate cover letter (if enabled)                       │
│         └── Submit application                                       │
│         │                                                            │
│         ▼                                                            │
│     Create AutoApplication record                                    │
│         │                                                            │
│         ▼                                                            │
│     Update AgentLearningMetric                                       │
│                                                                      │
│  REQUIRED IMPROVEMENTS:                                              │
│  • Kill-switch mechanism                                             │
│  • Human-in-the-loop approval                                        │
│  • Real API integrations (LinkedIn Partner, Indeed Publisher)        │
│  • Rate limiting enforcement                                         │
│  • Audit logging for every action                                    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.5 S.C.O.U.T. Employer Pipeline

```
┌─────────────────────────────────────────────────────────────────────┐
│                    S.C.O.U.T. PREDICTIVE HIRING                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Application Received                                             │
│     ApplicationSubmitted event                                       │
│         │                                                            │
│         ▼                                                            │
│     Employer notified                                                │
│                                                                      │
│  2. Resume Analysis                                                  │
│     POST /api/scout/analyze-resume                                   │
│         │                                                            │
│         ▼                                                            │
│     Scout\ResumeAnalyzerService.analyze()                            │
│         │                                                            │
│         ├── Extract skills                                           │
│         ├── Parse experience                                         │
│         ├── Identify education                                       │
│         └── Calculate initial fit score                              │
│                                                                      │
│  3. Generate Assessment                                              │
│     POST /api/scout/assessment/generate                              │
│         │                                                            │
│         ▼                                                            │
│     DynamicAssessmentService.generate()                              │
│         │                                                            │
│         ▼                                                            │
│     Create adaptive assessment questions                             │
│                                                                      │
│  4. Candidate Takes Assessment                                       │
│     POST /api/scout/assessment/{id}/submit                           │
│         │                                                            │
│         ▼                                                            │
│     Score responses                                                  │
│                                                                      │
│  5. Behavioral Assessment                                            │
│     POST /api/scout/behavioral/generate                              │
│         │                                                            │
│         ▼                                                            │
│     BehavioralIntelligenceService.generate()                         │
│         │                                                            │
│         ▼                                                            │
│     Create situational scenarios                                     │
│                                                                      │
│  6. Predictive Analytics                                             │
│     POST /api/scout/predictive/success                               │
│         │                                                            │
│         ▼                                                            │
│     PredictiveAnalyticsService.predictSuccess()                      │
│         │                                                            │
│         ├── Success probability                   ───┐               │
│         ├── Tenure forecast                          │               │
│         ├── Productivity estimate                    │               │
│         ├── Flight risk assessment                   ├── Store all   │
│         ├── Development plan                         │   predictions │
│         ├── Onboarding plan                          │               │
│         └── Career path prediction                ───┘               │
│                                                                      │
│  7. Bias Elimination                                                 │
│     POST /api/scout/bias/anonymize                                   │
│         │                                                            │
│         ▼                                                            │
│     BiasEliminationService.anonymize()                               │
│         │                                                            │
│         ├── Remove demographic identifiers                           │
│         ├── Normalize data                                           │
│         └── Create AnonymizedScreening record                        │
│                                                                      │
│     POST /api/scout/bias/audit                                       │
│         │                                                            │
│         ▼                                                            │
│     BiasEliminationService.auditDecision()                           │
│         │                                                            │
│         ├── Check for proxy discrimination                           │
│         ├── Generate fairness metrics                                │
│         └── Create BiasAuditResult                                   │
│                                                                      │
│  8. Decision & Traceability                                          │
│     GET /api/scout/bias/explanation/{application}                    │
│         │                                                            │
│         ▼                                                            │
│     Generate explainable decision rationale                          │
│     [MISSING: Store "why" artifacts for compliance]                  │
│                                                                      │
│  9. Pipeline Management                                              │
│     POST /api/scout/pipeline/{id}/add-candidate                      │
│         │                                                            │
│         ▼                                                            │
│     TalentPipelineService.addCandidate()                             │
│                                                                      │
│     GET /api/scout/silver-medalists                                  │
│         │                                                            │
│         ▼                                                            │
│     Return previously rejected strong candidates                     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 5. AI Integration Layer

### AI Service Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                       AI SERVICE LAYER                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │                    AIService (Main Wrapper)                    │  │
│  │                    app/Services/AI/AIService.php               │  │
│  │                                                                │  │
│  │  Public Methods:                                               │  │
│  │  • generateText(prompt, systemPrompt, options)                 │  │
│  │  • generateJSON(prompt, systemPrompt, options)                 │  │
│  │  • generateEmbeddings(text)                                    │  │
│  │  • forUser(User) - set user for credit tracking                │  │
│  │                                                                │  │
│  │  Protected Methods:                                            │  │
│  │  • callAI() - primary with fallback                            │  │
│  │  • callAzureOpenAI() - GPT-5.1 via Azure                       │  │
│  │  • callAzureAnthropic() - Claude Sonnet 4.5 via Azure          │  │
│  │  • trackUsage() - log tokens, cost                             │  │
│  │  • deductAICredits() - subscription credit management          │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                              │                                       │
│              ┌───────────────┴───────────────┐                       │
│              │                               │                       │
│              ▼                               ▼                       │
│  ┌───────────────────────┐       ┌───────────────────────┐          │
│  │    Azure OpenAI       │       │   Azure Anthropic     │          │
│  │    (PRIMARY)          │       │   (FALLBACK)          │          │
│  │                       │       │                       │          │
│  │  Model: GPT-5.1       │       │  Model: Claude        │          │
│  │  API: 2024-12-01      │       │  Sonnet 4.5           │          │
│  │  Timeout: 30s         │       │  Max Tokens: 4096     │          │
│  └───────────────────────┘       └───────────────────────┘          │
│                                                                      │
│  Fallback Flow:                                                      │
│  1. Try Azure OpenAI                                                 │
│  2. On failure → Log warning → Try Azure Anthropic                   │
│  3. On both fail → Return cached response or generic error           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### AI-Powered Services Dependency Map

```
AIService
    │
    ├── ResumeAIService
    │   └── Resume analysis, summary generation, skill extraction
    │
    ├── InterviewQuestionGenerator
    │   └── Generate role-specific interview questions
    │
    ├── AnswerEvaluationService
    │   └── Score and feedback on interview answers
    │
    ├── CoverLetterGeneratorService
    │   └── Personalized cover letter generation
    │
    ├── SkillGapAnalyzerService
    │   └── Identify skill gaps vs target role
    │
    ├── NegotiationStrategistService
    │   └── Salary negotiation strategies
    │
    ├── MarketIntelligenceService
    │   └── Market trends and salary insights
    │
    ├── CareerAdvisorService
    │   └── Career path recommendations
    │
    └── Scout Services
        ├── CorporateDNADecoderService
        ├── PredictiveAnalyticsService
        ├── BehavioralIntelligenceService
        ├── DynamicAssessmentService
        └── BiasEliminationService
```

---

## 6. Payment Processing

### Payment Gateway Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PAYMENT FLOW (RAZORPAY)                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  User                App                 Razorpay              DB    │
│   │                   │                     │                   │    │
│   │  Select Plan      │                     │                   │    │
│   │ ────────────────► │                     │                   │    │
│   │                   │                     │                   │    │
│   │                   │  Create Order       │                   │    │
│   │                   │ ──────────────────► │                   │    │
│   │                   │                     │                   │    │
│   │                   │  ◄──────────────── │                   │    │
│   │                   │  order_id           │                   │    │
│   │                   │                     │                   │    │
│   │                   │  Create Transaction ──────────────────► │    │
│   │                   │  (status: pending)  │                   │    │
│   │                   │                     │                   │    │
│   │  ◄─────────────── │  Checkout Modal     │                   │    │
│   │  (Razorpay JS)    │                     │                   │    │
│   │                   │                     │                   │    │
│   │  Complete Payment │                     │                   │    │
│   │ ──────────────────────────────────────► │                   │    │
│   │                   │                     │                   │    │
│   │                   │  ◄──────────────── │                   │    │
│   │                   │  Callback (success) │                   │    │
│   │                   │  {payment_id, sig}  │                   │    │
│   │                   │                     │                   │    │
│   │                   │  Verify Signature   │                   │    │
│   │                   │                     │                   │    │
│   │                   │  Update Transaction ──────────────────► │    │
│   │                   │  (status: success)  │                   │    │
│   │                   │                     │                   │    │
│   │                   │  Activate Subscription ───────────────► │    │
│   │                   │                     │                   │    │
│   │                   │  Send Notification  │                   │    │
│   │                   │                     │                   │    │
│   │  ◄─────────────── │  Success Page       │                   │    │
│   │                   │                     │                   │    │
│                                                                      │
│  WEBHOOK FLOW (Async Verification):                                  │
│  POST /webhooks/razorpay                                             │
│      │                                                               │
│      ▼                                                               │
│  Verify webhook signature                                            │
│      │                                                               │
│      ▼                                                               │
│  Update transaction if callback missed                               │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 7. Event-Driven Processing

### Current Implementation (Minimal)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    EVENT SYSTEM (CURRENT)                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Events Defined: 2                                                   │
│  Listeners: 3                                                        │
│  Subscribers: 1                                                      │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │                 ApplicationSubmitted                         │    │
│  │                                                              │    │
│  │  Dispatched by: ApplicationController, JobMatchingController │    │
│  │                                                              │    │
│  │  Listeners:                                                  │    │
│  │  └── SendApplicationSubmittedNotification                    │    │
│  │      └── Notify employer via email/database                  │    │
│  │                                                              │    │
│  │  Subscriber:                                                 │    │
│  │  └── GamificationEventSubscriber                             │    │
│  │      └── Award points to user                                │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │              ApplicationStatusChanged                        │    │
│  │                                                              │    │
│  │  Dispatched by: ApplicantTrackingController, API endpoints   │    │
│  │                                                              │    │
│  │  Listeners:                                                  │    │
│  │  └── SendApplicationStatusChangedNotification                │    │
│  │      └── Notify applicant of status change                   │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │                       Registered                             │    │
│  │                    (Laravel Default)                         │    │
│  │                                                              │    │
│  │  Listeners:                                                  │    │
│  │  └── SendEmailVerificationNotification                       │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  MISSING EVENTS (Should be implemented):                             │
│  • UserRegistered                • PaymentSucceeded                  │
│  • ProfileCompleted              • PaymentFailed                     │
│  • ResumeUploaded                • SubscriptionActivated             │
│  • InterviewSessionStarted       • AgentActivated                    │
│  • InterviewSessionCompleted     • AgentApplicationSubmitted         │
│  • SkillAssessmentCompleted      • CandidateShortlisted              │
│  • LearningPathStarted           • PredictionGenerated               │
│  • LearningPathCompleted         • BiasAuditCompleted                │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 8. Background Job System

### Job Queue Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                    QUEUE SYSTEM                                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Queue Driver: Redis (configured), SQLite (development)              │
│  Jobs: 37 total                                                      │
│  Priority Queues: Not implemented (should be 6 levels)               │
│                                                                      │
│  Job Categories:                                                     │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  APPLICATION & AGENT (6 jobs)                                 │   │
│  │  • ProcessAutoApplications - Agent auto-apply cycle           │   │
│  │  • DiscoverPassiveCandidatesJob - Find passive candidates     │   │
│  │  • SendCandidateUpdatesJob - Bulk notifications               │   │
│  │  • ... 3 more                                                 │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  MARKET INTELLIGENCE (5 jobs)                                 │   │
│  │  • UpdateMarketDataJob - Refresh market data                  │   │
│  │  • AnalyzeTrendsJob - Calculate trends                        │   │
│  │  • UpdateMarketTrendsJob - Update stored trends               │   │
│  │  • GenerateInsightsJob - Generate AI insights                 │   │
│  │  • UpdateUserPositioningJob - Update user market position     │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  SKILLS & LEARNING (4 jobs)                                   │   │
│  │  • AnalyzeSkillGapsJob - Analyze user skill gaps              │   │
│  │  • CurateLearningResourcesJob - Find learning resources       │   │
│  │  • SendDailyLearningRecommendationJob - Daily digest          │   │
│  │  • ValidateUserSkillsJob - Verify skill claims                │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  S.C.O.U.T. EMPLOYER (6 jobs)                                 │   │
│  │  • AnalyzeCompanyDNAJob - Corporate DNA analysis              │   │
│  │  • UpdateHiringPatternsJob - Historical hiring data           │   │
│  │  • GenerateCandidateMatchScoresJob - Batch scoring            │   │
│  │  • RefreshTeamDynamicsJob - Team analysis                     │   │
│  │  • AutomatedShortlistingJob - AI shortlisting                 │   │
│  │  • UpdateTalentPipelinesJob - Pipeline maintenance            │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  AI & ML (6 jobs)                                             │   │
│  │  • GenerateAssessmentJob - Create assessments                 │   │
│  │  • GenerateBehavioralAssessmentJob - Behavioral tests         │   │
│  │  • UpdatePredictionsJob - Refresh predictions                 │   │
│  │  • RefineLearningModelJob - Model training                    │   │
│  │  • AuditBiasJob - Bias detection                              │   │
│  │  • TrackEmployerBrandJob - Employer branding                  │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  VIDEO PROCESSING (4 jobs)                                    │   │
│  │  • ProcessVideoRecording - Video upload handling              │   │
│  │  • TranscribeVideoRecording - Speech-to-text                  │   │
│  │  • AnalyzeVideoInterview - AI video analysis                  │   │
│  │  • ... 1 more                                                 │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  GENERAL (6 jobs)                                             │   │
│  │  • ProcessJobAlerts - Send job alert notifications            │   │
│  │  • DeliverWebhook - Webhook delivery with retry               │   │
│  │  • SendBulkEmail - Mass email sending                         │   │
│  │  • GenerateUserDataExport - GDPR export                       │   │
│  │  • GenerateJobEmbeddings - Vector embedding generation        │   │
│  │  • ... 1 more                                                 │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  RECOMMENDED PRIORITY STRUCTURE:                                     │
│  high     → Payments, Auth, Critical notifications                   │
│  default  → Standard operations                                      │
│  low      → Background tasks, analytics                              │
│  ai       → AI-intensive operations                                  │
│  search   → Indexing, embeddings                                     │
│  reports  → Report generation                                        │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 9. Data Flow Diagrams

### User Data Flow

```
User Input
    │
    ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  FormRequest│────►│  Controller │────►│   Service   │
│  (Validate) │     │   (HTTP)    │     │  (Business) │
└─────────────┘     └─────────────┘     └──────┬──────┘
                                               │
                    ┌──────────────────────────┤
                    │                          │
                    ▼                          ▼
            ┌─────────────┐            ┌─────────────┐
            │   Model     │            │   External  │
            │ (Eloquent)  │            │    APIs     │
            └──────┬──────┘            └──────┬──────┘
                   │                          │
                   ▼                          │
            ┌─────────────┐                   │
            │  Database   │◄──────────────────┘
            │   (MySQL)   │
            └─────────────┘
```

### AI Request Flow

```
Feature Request (Resume Analysis, Interview Prep, etc.)
    │
    ▼
┌─────────────────────────────────────────────────────────┐
│                    Service Layer                         │
│  (ResumeAIService, InterviewQuestionGenerator, etc.)     │
└───────────────────────────┬─────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│                      AIService                           │
│                                                          │
│  1. Check user credits                                   │
│  2. Check cache for existing response                    │
│  3. Build prompt with system context                     │
└───────────────────────────┬─────────────────────────────┘
                            │
            ┌───────────────┴───────────────┐
            │                               │
            ▼                               ▼ (if primary fails)
┌─────────────────────┐         ┌─────────────────────┐
│   Azure OpenAI      │         │  Azure Anthropic    │
│   GPT-5.1           │         │  Claude Sonnet 4.5  │
└──────────┬──────────┘         └──────────┬──────────┘
           │                               │
           └───────────────┬───────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                   Post-Processing                        │
│                                                          │
│  1. Parse response (extract JSON if needed)              │
│  2. Track usage (tokens, cost)                           │
│  3. Deduct user credits                                  │
│  4. Cache response                                       │
│  5. Store in AIConversation table                        │
└───────────────────────────┬─────────────────────────────┘
                            │
                            ▼
                    Return to Service
```

---

## Summary

This System Blueprint documents the actual runtime architecture of StudAI Career as verified through code analysis. Key takeaways:

1. **Strong Foundation**: Laravel architecture properly followed with clear separation of concerns
2. **AI Layer Robust**: Azure OpenAI with Anthropic fallback is well-implemented
3. **Event System Weak**: Only 2 events - needs expansion for true event-driven architecture
4. **Agent System Non-Functional**: Scrapers return demo data - requires API partnerships
5. **Payments Working**: Razorpay + PayU integrated, needs Stripe for global expansion
6. **Observability Missing**: No Sentry, correlation IDs, or Horizon configured

For production deployment, prioritize the items identified in the Implementation Backlog document.
