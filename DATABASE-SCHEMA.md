# StudAI Career Platform - Database Schema Documentation

**Last Updated**: October 28, 2025  
**Database**: MySQL/MariaDB 11.8.3  
**Total Tables**: 28  
**Database Size**: 1.36 MB

---

## 📊 Database Overview

The StudAI Career platform uses a comprehensive relational database schema designed for scalability, performance, and AI-powered features. All tables use UTF-8 MB4 encoding with InnoDB engine.

### Schema Categories

1. **User Management** (2 tables): users, profiles
2. **Company & Industry** (3 tables): companies, industries, company_industry
3. **Job Listings** (4 tables): jobs, skills, saved_jobs, job_alerts
4. **Applications** (1 table): applications
5. **Subscriptions & Payments** (3 tables): subscription_plans, user_subscriptions, payment_transactions
6. **AI Services** (2 tables): ai_conversations, ai_usage_logs
7. **Authentication & Permissions** (5 tables): permissions, roles, model_has_permissions, model_has_roles, role_has_permissions
8. **System** (8 tables): migrations, cache, cache_locks, sessions, password_reset_tokens, personal_access_tokens, failed_jobs, job_batches

---

## 👥 User Management

### `users` (16 columns, 96 KB)

Core user authentication and account management.

**Key Fields**:
- `id` - Primary key
- `name`, `email`, `phone` - Basic contact info
- `account_type` - Enum: `job_seeker`, `employer`, `admin`
- `avatar` - Profile picture path
- `is_active` - Account status
- `preferences` - JSON: User settings
- `last_login_at` - Activity tracking
- `timezone` - User timezone
- `deleted_at` - Soft delete timestamp

**Indexes**:
- Primary: `id`
- Unique: `email`
- Compound: `[email, account_type]`

**Design Notes**:
- Multi-guard authentication support (job_seeker, employer, admin)
- Soft deletes enabled for data retention
- JSON preferences for flexible settings

---

### `profiles` (27 columns, 32 KB)

Extended user profile information for job seekers.

**Key Fields**:
- `user_id` - Foreign key to users
- `headline`, `summary` - Profile intro
- `experience` - JSON: Work history array
- `education` - JSON: Education history
- `skills` - JSON: Skills with proficiency levels
- `languages` - JSON: Language proficiencies
- `current_location`, `preferred_locations` - Location preferences
- `expected_salary_min/max` - Salary expectations
- `notice_period` - Availability
- `work_preference` - Enum: `remote`, `hybrid`, `onsite`
- `social_links` - JSON: LinkedIn, GitHub, etc.
- `profile_completeness` - 0-100 score
- `is_public`, `open_to_opportunities` - Visibility settings

**Indexes**:
- Foreign key: `user_id` → users.id (cascade delete)
- Compound: `[user_id, is_public]`

**Design Notes**:
- JSON columns for flexibility (no separate experience/education tables)
- Profile completeness calculated by AI or formula
- Public profiles visible to employers

---

## 🏢 Company & Industry

### `companies` (21 columns, 64 KB)

Company profiles and employer information.

**Key Fields**:
- `id` - Primary key
- `name`, `slug` - Company identification
- `description` - Company overview
- `logo`, `website` - Branding
- `industry`, `company_size` - Classification
- `founded_year`, `headquarters` - Company details
- `locations` - JSON: Multiple locations
- `benefits`, `tech_stack` - JSON: Perks and technologies
- `culture_rating` - 0-5 rating
- `is_verified`, `is_featured` - Status flags

**Indexes**:
- Primary: `id`
- Unique: `slug`
- Compound: `[slug, is_verified]`
- Full-text: `[name, description]`

**Design Notes**:
- Verified companies get badge
- Featured companies shown prominently
- Full-text search for company discovery

---

### `industries` (9 columns, 64 KB)

Industry categories master table.

**Key Fields**:
- `id` - Primary key
- `name`, `slug` - Industry name
- `description` - Industry overview
- `icon` - Icon identifier
- `jobs_count` - Cached count
- `is_active` - Visibility

**Indexes**:
- Primary: `id`
- Unique: `name`, `slug`
- Index: `slug`

---

### `company_industry` (4 columns, 48 KB)

Many-to-many pivot for company-industry relationships.

**Foreign Keys**:
- `company_id` → companies.id (cascade delete)
- `industry_id` → industries.id (cascade delete)

**Unique Constraint**: `[company_id, industry_id]`

---

## 💼 Job Listings

### `jobs` (31 columns, 96 KB)

Core job postings table with AI features.

**Key Fields**:
- `company_id`, `posted_by` - Company and user references
- `title`, `slug` - Job identification
- `description` - Job details
- `requirements`, `responsibilities`, `nice_to_have` - JSON: Job specs
- `employment_type` - e.g., "full-time", "contract"
- `experience_level` - e.g., "entry", "senior"
- `salary_min/max`, `salary_currency` - Compensation
- `salary_negotiable` - Boolean
- `location`, `work_mode` - Enum: `remote`, `hybrid`, `onsite`
- `benefits` - JSON: Perks array
- `openings` - Number of positions
- `deadline` - Application deadline
- `status` - Enum: `draft`, `active`, `paused`, `closed`
- `views`, `applications_count` - Metrics
- `is_featured`, `is_urgent` - Priority flags
- `ai_insights` - JSON: AI-generated job analysis
- `extracted_skills` - JSON: AI-extracted required skills
- `quality_score` - 0-100 AI quality assessment

**Indexes**:
- Primary: `id`
- Unique: `slug`
- Compound: `[company_id, status]`, `[posted_by, status]`, `[status, created_at]`
- Full-text: `[title, description]`

**Foreign Keys**:
- `company_id` → companies.id (cascade)
- `posted_by` → users.id (cascade)

**Design Notes**:
- Full-text search for job discovery
- AI analyzes job quality and extracts skills
- Soft deletes for archiving
- INR as default currency (Indian market)

---

### `skills` (18 columns, 80 KB)

Master skills catalog.

**Key Fields**:
- `name`, `slug` - Skill name
- `category` - e.g., "technical", "soft", "language"
- `description` - Skill details
- `demand_index` - 0-100 market demand score
- `related_skills` - JSON: Related skills
- `learning_resources` - JSON: Courses, tutorials
- `is_trending` - Trending flag

**Indexes**:
- Primary: `id`
- Unique: `name`, `slug`
- Compound: `[category, demand_index]`

**Design Notes**:
- AI updates demand_index based on job postings
- Trending skills highlighted to users

---

### `saved_jobs` (6 columns, 64 KB)

User bookmarked jobs.

**Foreign Keys**:
- `user_id` → users.id (cascade)
- `job_id` → jobs.id (cascade)

**Unique Constraint**: `[user_id, job_id]`

**Key Fields**:
- `notes` - User notes on saved job

---

### `job_alerts` (10 columns, 48 KB)

Automated job notifications.

**Key Fields**:
- `user_id` - User reference
- `name` - Alert name
- `criteria` - JSON: Search filters (keywords, location, salary, etc.)
- `frequency` - Enum: `instant`, `daily`, `weekly`
- `is_active` - Alert status
- `last_sent_at` - Last notification time
- `matches_count` - Number of matches found

**Indexes**:
- Compound: `[user_id, is_active]`
- Index: `last_sent_at`

---

## 📝 Applications

### `applications` (29 columns, 80 KB)

Job application tracking with AI matching.

**Key Fields**:
- `job_id`, `user_id` - Application references
- `application_number` - Unique identifier (e.g., "APP-2025-001234")
- `cover_letter` - User's cover letter
- `resume_file` - Resume file path
- `answers` - JSON: Screening question responses
- `status` - Enum: `draft`, `submitted`, `viewed`, `shortlisted`, `interview_scheduled`, `interviewed`, `offered`, `accepted`, `rejected`, `withdrawn`
- `match_score` - 0-100 AI match percentage
- `match_analysis` - JSON: Detailed AI match breakdown
- `timeline` - JSON: Status change history
- `notes` - Recruiter notes
- `submitted_at`, `viewed_at` - Activity timestamps

**Indexes**:
- Primary: `id`
- Unique: `application_number`, `[job_id, user_id]`
- Compound: `[user_id, status]`, `[job_id, status]`

**Foreign Keys**:
- `job_id` → jobs.id (cascade)
- `user_id` → users.id (cascade)

**Design Notes**:
- One application per user per job (unique constraint)
- AI calculates match score and provides analysis
- Timeline tracks all status changes for audit trail

---

## 💳 Subscriptions & Payments

### `subscription_plans` (26 columns, 64 KB)

Subscription tier definitions.

**Key Fields**:
- `name`, `slug` - Plan name
- `description` - Plan details
- `razorpay_plan_id`, `payu_plan_id` - Gateway IDs
- `price`, `currency` - Pricing (INR default)
- `billing_period` - Enum: `monthly`, `yearly`
- `features` - JSON: Feature array
- `ai_credits` - Monthly AI credits
- `applications_limit` - Monthly application limit (null = unlimited)
- `job_alerts_limit` - Alert limit
- `priority_support`, `api_access` - Premium features
- `api_calls_limit` - API usage limit
- `is_active`, `is_featured` - Visibility
- `sort_order` - Display order

**Indexes**:
- Primary: `id`
- Unique: `slug`
- Compound: `[is_active, sort_order]`

**Design Notes**:
- Supports both Razorpay and PayU (Indian payment gateways)
- No Stripe/Cashier (intentional for Indian market)

---

### `user_subscriptions` (19 columns, 64 KB)

Active user subscriptions.

**Key Fields**:
- `user_id`, `subscription_plan_id` - References
- `payment_gateway` - Enum: `razorpay`, `payu`
- `gateway_subscription_id` - Gateway reference
- `status` - Enum: `active`, `canceled`, `expired`, `trialing`
- `starts_at`, `ends_at` - Subscription period
- `trial_ends_at` - Trial period
- `current_period_start/end` - Billing period
- `applications_used_this_month`, `ai_credits_used_this_month` - Usage tracking
- `canceled_at` - Cancellation timestamp

**Indexes**:
- Compound: `[user_id, status]`
- Index: `ends_at`

**Foreign Keys**:
- `user_id` → users.id (cascade)
- `subscription_plan_id` → subscription_plans.id (cascade)

**Design Notes**:
- Usage counters reset monthly
- Supports trial periods
- Tracks which gateway was used

---

### `payment_transactions` (19 columns, 96 KB)

Payment transaction log (gateway-agnostic).

**Key Fields**:
- `user_id`, `subscription_plan_id` - References
- `transaction_id` - Unique identifier
- `payment_gateway` - Enum: `razorpay`, `payu`
- `order_id`, `payment_id`, `signature` - Gateway identifiers
- `amount`, `currency` - Transaction amount
- `status` - Enum: `pending`, `success`, `failed`, `refunded`
- `payment_method` - e.g., "card", "upi", "netbanking"
- `error_message` - Failure reason
- `gateway_response` - JSON: Full gateway response
- `paid_at` - Payment timestamp

**Indexes**:
- Primary: `id`
- Unique: `transaction_id`
- Index: `order_id`
- Compound: `[user_id, status]`

**Foreign Keys**:
- `user_id` → users.id (cascade)
- `subscription_plan_id` → subscription_plans.id

**Design Notes**:
- Gateway-agnostic design (works with Razorpay OR PayU)
- Stores full gateway response for debugging
- Webhook and callback verification support

---

## 🤖 AI Services

### `ai_conversations` (10 columns, 48 KB)

AI chat history tracking.

**Key Fields**:
- `user_id` - User reference
- `context` - Enum: `resume_review`, `interview_prep`, `career_advice`, `cover_letter`, `job_match`, `skills_gap`
- `messages` - JSON: Conversation history
- `tokens_used` - Total tokens consumed
- `cost` - Total cost in USD
- `session_id` - Session identifier

**Indexes**:
- Compound: `[user_id, context]`
- Index: `created_at`

**Foreign Key**: `user_id` → users.id (cascade)

**Design Notes**:
- Tracks AI usage by context
- Messages stored as JSON array
- Cost tracking for billing

---

### `ai_usage_logs` (13 columns, 64 KB)

Detailed AI API usage tracking.

**Key Fields**:
- `user_id` - User reference
- `feature` - Enum: `resume_analysis`, `job_matching`, `cover_letter`, `interview_prep`, `career_advice`, `skills_extraction`
- `model` - e.g., "gpt-4o", "text-embedding-3-large"
- `input_tokens`, `output_tokens`, `total_tokens` - Token usage
- `cost_usd` - API cost
- `response_time_ms` - Response time
- `metadata` - JSON: Additional context

**Indexes**:
- Compound: `[user_id, created_at]`, `[feature, created_at]`
- Index: `model`

**Foreign Key**: `user_id` → users.id (cascade)

**Design Notes**:
- Tracks every AI API call
- Used for cost analysis and billing
- Response time tracking for performance monitoring

---

## 🔐 Authentication & Permissions

### Spatie Laravel Permission Tables (5 tables)

- `permissions` (32 KB) - Permission definitions
- `roles` (32 KB) - Role definitions
- `model_has_permissions` (32 KB) - User permissions
- `model_has_roles` (32 KB) - User roles
- `role_has_permissions` (32 KB) - Role permissions

**Design Notes**:
- Implements RBAC (Role-Based Access Control)
- Flexible permission system
- Supports role inheritance

---

## ⚙️ System Tables

### Authentication
- `password_reset_tokens` (16 KB) - Password reset tokens
- `personal_access_tokens` (64 KB) - Sanctum API tokens
- `sessions` (48 KB) - User sessions

### Queue & Jobs
- `failed_jobs` (32 KB) - Failed queue jobs
- `job_batches` (16 KB) - Batched jobs

### Cache
- `cache` (16 KB) - Application cache
- `cache_locks` (16 KB) - Cache locking

### Migrations
- `migrations` (16 KB) - Migration history

---

## 📈 Performance Optimization

### Indexing Strategy

1. **Primary Keys**: All tables have auto-incrementing bigint IDs
2. **Foreign Keys**: All relationships indexed
3. **Compound Indexes**: For common query patterns
4. **Full-Text Search**: Jobs and companies tables
5. **Unique Constraints**: Prevent duplicate data

### Query Optimization

- **Eager Loading**: Use `with()` for relationships
- **Scopes**: Define reusable query scopes
- **Caching**: Cache frequently accessed data (companies, skills)
- **Pagination**: Always paginate large result sets

### JSON Columns

Used for flexible data storage:
- User preferences
- Profile experience/education/skills
- Job requirements/responsibilities
- AI analysis results
- Payment gateway responses

**Benefits**:
- No schema changes needed for new fields
- Flexible data structure
- Reduced table count

**Considerations**:
- Cannot index JSON fields (use computed columns if needed)
- Validate JSON structure in application layer

---

## 🔄 Relationships

### One-to-One
- User → Profile

### One-to-Many
- User → Applications
- User → Saved Jobs
- User → Job Alerts
- Company → Jobs
- Job → Applications

### Many-to-Many
- Company ↔ Industry (via company_industry)
- User ↔ Permission (via model_has_permissions)
- User ↔ Role (via model_has_roles)
- Role ↔ Permission (via role_has_permissions)

### Polymorphic
- Spatie Permission uses polymorphic relationships for flexible assignment

---

## 🚀 Migration Execution

All migrations were executed successfully:

```bash
php artisan migrate
```

**Results**:
- ✅ 15 new migrations executed
- ✅ 28 total tables created
- ✅ All foreign keys established
- ✅ All indexes created
- ✅ Full-text search enabled on jobs and companies

---

## 📝 Next Steps

1. **Create Model Classes**: Generate Eloquent models for all tables
2. **Define Relationships**: Implement Eloquent relationships
3. **Create Seeders**: Populate with test data
4. **Write Factories**: Generate fake data for testing
5. **Build Observers**: Handle model events (creating, created, etc.)
6. **Implement Scopes**: Define reusable query scopes
7. **Add Accessors/Mutators**: Transform data on retrieval/storage

---

**Database Schema Version**: 1.0  
**Last Migration**: 2025_10_28_162841_create_job_alerts_table
