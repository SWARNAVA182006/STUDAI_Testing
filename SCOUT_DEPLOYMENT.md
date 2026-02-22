# S.C.O.U.T. Deployment Guide
## Strategic Corporate Optimization & Understanding Tool

**Version:** 1.0  
**Last Updated:** November 6, 2025  
**Laravel Version:** 11.x  
**PHP Version:** 8.2+

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Prerequisites](#prerequisites)
3. [Environment Configuration](#environment-configuration)
4. [Database Setup](#database-setup)
5. [Queue Configuration](#queue-configuration)
6. [AI Service Configuration](#ai-service-configuration)
7. [Employer Onboarding Flow](#employer-onboarding-flow)
8. [DNA Analysis Process](#dna-analysis-process)
9. [Candidate Matching Guide](#candidate-matching-guide)
10. [Team Compatibility Assessment](#team-compatibility-assessment)
11. [Intelligent Resume Analysis](#intelligent-resume-analysis)
12. [Multi-Stage Automated Shortlisting](#multi-stage-automated-shortlisting)
13. [Privacy & Compliance](#privacy--compliance)
14. [API Rate Limiting](#api-rate-limiting)
15. [Monitoring & Alerting](#monitoring--alerting)
16. [Testing Checklist](#testing-checklist)
17. [Troubleshooting](#troubleshooting)
18. [Rollback Plan](#rollback-plan)

---

## System Overview

S.C.O.U.T. is an AI-powered organizational DNA analysis and candidate matching system that:

- **Analyzes company culture** using Hofstede cultural dimensions
- **Identifies hiring patterns** from historical recruitment data
- **Predicts candidate success** using multi-dimensional scoring (Cultural 35%, Skill 30%, Work Style 20%, Performance 15%)
- **Assesses team compatibility** for optimal new hire integration
- **Analyzes resumes semantically** beyond keyword matching with transferable skill identification
- **Automates shortlisting** through 4-round evaluation pipeline
- **Provides actionable insights** powered by GPT-4 analysis

### Key Components

- **5 Database Tables**: company_dna_profiles, culture_analyses, hiring_patterns, success_indicators, team_dynamics
- **5 AI Services**: CorporateDNADecoderService, HiringPatternAnalyzerService, TeamDynamicsAnalyzerService, SuccessPredictorService, ResumeAnalyzerService, AutomatedShortlistingService
- **10 API Endpoints**: `/api/scout/*` with RESTful architecture
- **7 Dashboard Views**: DNA Dashboard, Culture Analysis, Candidate Matching, Team Compatibility, Hiring Insights, Resume Analysis, Automated Shortlisting
- **5 Background Jobs**: Weekly DNA analysis, post-hire pattern updates, daily candidate scoring, monthly team refresh, automated shortlisting

---

## Prerequisites

### Required Software

- **PHP** 8.2 or higher
- **Laravel** 11.x
- **MySQL** 8.0+ or PostgreSQL 15+
- **Redis** 7.0+ (for queues and caching)
- **Composer** 2.x
- **Node.js** 18+ and npm (for frontend assets)

### Required Services

- **OpenAI API Account** with GPT-4 access
  - Model used: `gpt-4o`
  - Recommended tier: Pay-as-you-go with $100+ credit
  - Expected monthly cost: $50-200 depending on company size

### Minimum Data Requirements

- **For DNA Analysis**: 5+ employees with profile data
- **For Hiring Patterns**: 5+ historical hires with application data
- **For Team Dynamics**: 3+ employees per department
- **For Candidate Matching**: Active DNA profile + candidate applications

### Server Requirements

- **CPU**: 2+ cores recommended
- **RAM**: 4GB minimum, 8GB recommended
- **Storage**: 20GB+ for application and database
- **PHP Extensions**: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Redis

---

## Environment Configuration

### 1. Core Laravel Settings

```env
APP_NAME="StudAI Career Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=studai_career
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Analytics Database (separate connection)
DB_ANALYTICS_CONNECTION=mysql_analytics
DB_ANALYTICS_HOST=127.0.0.1
DB_ANALYTICS_PORT=3306
DB_ANALYTICS_DATABASE=studai_career_analytics
DB_ANALYTICS_USERNAME=your_analytics_user
DB_ANALYTICS_PASSWORD=your_secure_password
```

### 2. Redis Configuration

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
```

### 3. OpenAI API Configuration

```env
# OpenAI API Key (required for GPT-4 access)
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxx

# OpenAI Model Configuration
OPENAI_MODEL=gpt-4o
OPENAI_MAX_TOKENS=2000
OPENAI_TEMPERATURE=0.7

# OpenAI Rate Limiting
OPENAI_REQUESTS_PER_MINUTE=60
OPENAI_RETRY_MAX_ATTEMPTS=3
```

### 4. S.C.O.U.T. Feature Flags

```env
# Enable/Disable S.C.O.U.T. System
SCOUT_ENABLED=true

# Minimum data thresholds
SCOUT_MIN_EMPLOYEES=5
SCOUT_MIN_HIRES=5
SCOUT_MIN_TEAM_SIZE=3

# Analysis confidence thresholds
SCOUT_MIN_CONFIDENCE=60
SCOUT_HIGH_CONFIDENCE=80

# Candidate matching thresholds
SCOUT_MIN_MATCH_SCORE=55
SCOUT_STRONG_MATCH_SCORE=70
SCOUT_EXCELLENT_MATCH_SCORE=85
```

### 5. Notification Settings

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@studai.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Database Setup

### 1. Run Migrations

```bash
# Run all migrations including S.C.O.U.T. tables
php artisan migrate

# Verify tables were created
php artisan db:show
```

### 2. Expected Tables

The following tables should be created:

- `company_dna_profiles` - Central organizational DNA data
- `culture_analyses` - Hofstede cultural dimensions analysis
- `hiring_patterns` - Historical recruitment analytics
- `success_indicators` - Employee success tracking
- `team_dynamics` - Department collaboration metrics

### 3. Seed Demo Data (Optional)

```bash
# Seed with realistic demo data for testing
php artisan db:seed --class=ScoutDemoDataSeeder
```

### 4. Database Indexes

Verify indexes are created for optimal performance:

```sql
-- Check indexes on key tables
SHOW INDEX FROM company_dna_profiles;
SHOW INDEX FROM hiring_patterns;
SHOW INDEX FROM team_dynamics;
```

Expected indexes:
- `company_id` on all S.C.O.U.T. tables
- `analyzed_at` for time-based queries
- `department` on team_dynamics
- Composite indexes for frequently joined queries

---

## Queue Configuration

### 1. Configure Queue Driver

```bash
# Update .env
QUEUE_CONNECTION=redis

# Clear config cache
php artisan config:clear
```

### 2. Supervisor Configuration

Create `/etc/supervisor/conf.d/studai-worker.conf`:

```ini
[program:studai-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/studai-career/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=900
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/studai-career/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start studai-worker:*
```

### 3. Configure Job Scheduling

Add to `routes/console.php`:

```php
use App\Jobs\AnalyzeCompanyDNAJob;
use App\Jobs\GenerateCandidateMatchScoresJob;
use App\Jobs\RefreshTeamDynamicsJob;
use App\Models\CompanyDNAProfile;

Schedule::call(function () {
    // Weekly DNA analysis for all companies (Mondays at 3 AM)
    CompanyDNAProfile::all()->each(function ($profile) {
        AnalyzeCompanyDNAJob::dispatch($profile->company_id)
            ->onQueue('scout-analysis');
    });
})->weekly()->mondays()->at('03:00');

// Daily candidate match scoring (4 AM)
Schedule::job(new GenerateCandidateMatchScoresJob())
    ->dailyAt('04:00')
    ->onQueue('scout-matching');

// Monthly team dynamics refresh (15th at 1 AM)
Schedule::job(new RefreshTeamDynamicsJob())
    ->monthlyOn(15, '01:00')
    ->onQueue('scout-analysis');
```

### 4. Start Queue Workers

```bash
# For development
php artisan queue:work redis --queue=scout-analysis,scout-matching,default --verbose

# Check queue status
php artisan queue:monitor scout-analysis,scout-matching

# View failed jobs
php artisan queue:failed
```

### 5. Manual Job Dispatch Commands

Create custom Artisan commands for testing:

```bash
# Analyze DNA for specific company
php artisan scout:analyze-dna {companyId} [--force]

# Refresh hiring patterns
php artisan scout:refresh-patterns {companyId}

# Score all candidates
php artisan scout:score-candidates [--company={companyId}]

# Refresh team dynamics
php artisan scout:refresh-teams [--company={companyId}]
```

---

## AI Service Configuration

### 1. OpenAI API Setup

1. Create account at https://platform.openai.com
2. Generate API key with GPT-4 access
3. Set billing limits to prevent overages
4. Add key to `.env` as `OPENAI_API_KEY`

### 2. Token Usage Estimation

**DNA Analysis** (per company):
- Mission/Vision/Values extraction: ~500 tokens
- Cultural DNA mapping: ~800 tokens  
- Success traits identification: ~600 tokens
- **Total**: ~1,900 tokens (~$0.04 per analysis)

**Hiring Pattern Analysis**:
- Source effectiveness: ~400 tokens
- Success/failure patterns: ~700 tokens
- Recommendations: ~500 tokens
- **Total**: ~1,600 tokens (~$0.03 per analysis)

**Candidate Matching** (per candidate):
- Multi-dimensional scoring: ~600 tokens
- Strengths/concerns: ~400 tokens
- Recommendations: ~300 tokens
- **Total**: ~1,300 tokens (~$0.03 per candidate)

**Team Dynamics**:
- Collaboration analysis: ~500 tokens
- Ideal candidate profile: ~400 tokens
- Recommendations: ~300 tokens
- **Total**: ~1,200 tokens (~$0.02 per team)

**Monthly Cost Estimate** (100 active companies):
- Weekly DNA: 100 × 4 × $0.04 = $16
- Post-hire patterns: ~20 hires × $0.03 = $0.60
- Daily candidates: 100 companies × 10 candidates × 30 days × $0.03 = $900
- Monthly teams: 100 × 5 departments × $0.02 = $10
- **Total**: ~$926/month

### 3. Rate Limiting

Configure in `config/services.php`:

```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'requests_per_minute' => env('OPENAI_REQUESTS_PER_MINUTE', 60),
    'retry_after' => 60, // seconds
    'max_retries' => 3,
],
```

### 4. Response Caching

AI responses are cached to reduce costs:

- **DNA Analysis**: 7 days (refreshes weekly)
- **Hiring Patterns**: 30 days (until new hire)
- **Candidate Matches**: 24 hours (refreshes daily)
- **Team Dynamics**: 30 days (refreshes monthly)

```bash
# Clear AI response cache
php artisan cache:forget scout_*
```

---

## Employer Onboarding Flow

### Minimum Requirements

**Data Volume**:
- ✅ **10+ employees** recommended for accurate DNA analysis
- ✅ **5+ historical hires** required for pattern analysis
- ✅ **Employee profiles** with skills, experience, work style preferences
- ✅ **Job descriptions** with clear requirements

**Profile Completeness**:
- Company mission/vision statements
- Core values (3-7 values recommended)
- Department structure
- Historical application data with hire decisions

### Onboarding Steps

#### Step 1: Company Profile Setup (5 minutes)

1. Complete company profile:
   - Name, industry, size
   - Mission statement (2-3 sentences)
   - Vision statement (2-3 sentences)
   - Core values (3-7 values)

2. Add department structure:
   - Engineering, Product, Design, Marketing, Sales, HR, Operations
   - Assign team leads

#### Step 2: Import Employee Data (10-30 minutes)

**Option A: Manual Entry**
- Add employees via dashboard
- Fill profile: skills, experience, work style, personality traits

**Option B: HRIS Integration**
- Connect to BambooHR, Workday, or Gusto
- Automated sync of employee data
- Map fields to S.C.O.U.T. schema

**Option C: CSV Import**
- Download template: `/downloads/employee-import-template.csv`
- Fill data for all employees
- Upload via dashboard

#### Step 3: Import Historical Hires (15-45 minutes)

Required fields per hire:
- Application date
- Job position
- Hire source (LinkedIn, referral, job board, etc.)
- Time to hire (days)
- Interview stages completed
- Offer acceptance (yes/no)
- Current employment status
- Performance rating (if available)

#### Step 4: Initial DNA Analysis (2-5 minutes)

1. Navigate to **S.C.O.U.T. Dashboard** → **DNA Profile**
2. Click **"Run DNA Analysis"**
3. System analyzes:
   - Employee profiles
   - Historical hires
   - Company values/mission
4. Wait for completion notification (email + in-app)

**Analysis Duration**:
- <25 employees: 2-3 minutes
- 25-100 employees: 3-5 minutes
- 100+ employees: 5-10 minutes

#### Step 5: Review DNA Results

Check **DNA Health Score** (0-100):
- **0-59**: Insufficient data - add more employees/hires
- **60-79**: Moderate confidence - usable but could improve
- **80-100**: High confidence - excellent data quality

Review generated insights:
- Cultural DNA traits (8 dimensions)
- Success traits ranking
- Work style preferences
- Communication patterns
- Cultural archetypes

#### Step 6: Configure Culture Analysis

1. Navigate to **Culture Analysis** view
2. Set Hofstede dimensions:
   - Power Distance (0-100)
   - Individualism (0-100)
   - Uncertainty Avoidance (0-100)
   - Long-term Orientation (0-100)
   - Indulgence (0-100)

3. Define work environment:
   - Remote-First / Hybrid / Office-Based

4. Add culture strengths and challenges

#### Step 7: Enable Automated Analysis

System automatically:
- ✅ Refreshes DNA weekly (Mondays 3 AM)
- ✅ Updates patterns after each hire
- ✅ Scores active candidates daily (4 AM)
- ✅ Refreshes team dynamics monthly (15th, 1 AM)

---

## DNA Analysis Process

### Data Collection

**Employee Data Sources**:
- Profile fields: skills, experience, education, values
- Work style preferences: collaboration vs. independent, structure vs. flexibility
- Personality traits: leadership, creativity, analytical thinking
- Performance ratings and tenure

**Company Data Sources**:
- Mission/vision statements
- Core values
- Job descriptions
- Success criteria per role

**Historical Data Sources**:
- Application timestamps and sources
- Interview feedback
- Hire decisions
- Retention data
- Performance reviews

### Analysis Workflow

1. **Data Validation** (30 seconds)
   - Check minimum thresholds
   - Verify data completeness
   - Calculate preliminary confidence

2. **GPT-4 Analysis** (1-2 minutes)
   - Extract cultural DNA from company identity
   - Identify success traits from top performers
   - Map work style preferences
   - Detect communication patterns

3. **Pattern Recognition** (30-60 seconds)
   - Cluster similar employee profiles
   - Identify cultural archetypes
   - Calculate trait frequencies
   - Determine retention correlations

4. **Score Calculation** (10 seconds)
   - DNA Health Score (0-100)
   - Completion Score (0-100)
   - Confidence Score (0-100)
   - Data Quality Score (0-100)

5. **Cache & Notify** (5 seconds)
   - Store results in database
   - Cache for 7 days
   - Send email notification
   - Update dashboard

### Confidence Score Thresholds

**Insufficient (<60)**:
- Needs 5+ more employees OR 3+ more hires
- Missing critical data (mission/values)
- Incomplete employee profiles (<50% fields)
- **Action**: Add more data before using for hiring decisions

**Moderate (60-79)**:
- Usable for general insights
- May have some blind spots
- Pattern confidence: 70-85%
- **Action**: Continue using, aim to reach 80+ for critical hires

**High (80-100)**:
- Excellent data quality
- Reliable for hiring decisions
- Pattern confidence: 90%+
- **Action**: Fully operational

### Data Quality Requirements

**For 60+ Confidence**:
- 5+ employees with complete profiles
- 5+ historical hires
- Mission statement
- 3+ core values

**For 80+ Confidence**:
- 10+ employees with complete profiles (>80% fields)
- 10+ historical hires with outcomes
- Mission, vision, values all defined
- 3+ departments with data
- Mix of successful and unsuccessful hires

**For 95+ Confidence**:
- 25+ employees with 90%+ profile completion
- 20+ historical hires with performance data
- Multi-year retention data
- Diverse hiring sources
- Interview feedback records

### Analysis Refresh Recommendations

**Weekly** (Default):
- Growing companies (<100 employees)
- High hiring velocity (>5 hires/month)
- Rapid culture changes

**Bi-weekly**:
- Stable companies (100-500 employees)
- Moderate hiring (2-5 hires/month)

**Monthly**:
- Large stable companies (500+ employees)
- Low hiring velocity (<2 hires/month)
- Established culture

**Trigger Refresh When**:
- Major organizational changes
- Leadership changes
- 10+ new hires since last analysis
- Acquisition or merger
- Strategic pivot

---

## Candidate Matching Guide

### Scoring Algorithm

**Multi-Dimensional Weights**:
- **Cultural Fit**: 35% - Alignment with company values, work style, communication
- **Skill Fit**: 30% - Technical skills, experience level, domain expertise
- **Work Style Fit**: 20% - Collaboration preferences, autonomy, pace
- **Performance Prediction**: 15% - Based on similar successful hires

### Score Interpretation

**Overall Success Score (0-100)**:

| Score Range | Fit Level | Recommendation | Action |
|-------------|-----------|----------------|--------|
| 85-100 | Excellent | Strong hire | Fast-track, prioritize |
| 70-84 | Strong | Good hire | Interview, assess culture |
| 55-69 | Moderate | Possible hire | Deep dive, verify fit |
| 0-54 | Weak | Not recommended | Pass or reassess role |

**Cultural Fit Score**:
- **90-100**: Perfect culture match - values, work style, communication align
- **75-89**: Strong match - minor differences, easily adaptable
- **60-74**: Moderate match - some misalignment, requires culture onboarding
- **<60**: Poor match - significant cultural differences, high risk

**Skill Fit Score**:
- **90-100**: Exceeds requirements - ready for immediate impact
- **75-89**: Meets requirements - can perform with minimal ramp-up
- **60-74**: Near requirements - needs 1-2 months training
- **<60**: Below requirements - significant skill gaps

**Work Style Fit Score**:
- **90-100**: Ideal work style - thrives in company environment
- **75-89**: Compatible - adapts well to work style
- **60-74**: Manageable differences - may need accommodation
- **<60**: Incompatible - work style conflicts likely

**Performance Prediction**:
- **90-100**: Very high success probability (>90%)
- **75-89**: High success probability (75-90%)
- **60-74**: Moderate success probability (60-75%)
- **<60**: Low success probability (<60%)

### Interpreting AI Recommendations

**"STRONG HIRE"**:
- Overall score 85+
- All dimensions 75+
- Similar to top performers
- **Action**: Make offer if interview confirms

**"RECOMMENDED HIRE"**:
- Overall score 70-84
- Most dimensions 70+
- Good culture + skill match
- **Action**: Proceed with interviews

**"CONDITIONAL HIRE"**:
- Overall score 55-69
- Mixed dimension scores
- Potential with support
- **Action**: Deep assessment, plan onboarding support

**"NOT RECOMMENDED"**:
- Overall score <55
- Poor cultural or skill fit
- High failure risk
- **Action**: Pass or consider different role

### Using Strengths & Concerns

**Strengths** (Green flags):
- Validate in interview
- Explore in depth
- Confirm with references
- Build on in onboarding

**Concerns** (Yellow flags):
- Address directly in interview
- Create mitigation plan
- Assess adaptability
- Provide extra support

### Common Matching Patterns

**High Culture, Lower Skill** (e.g., Cultural 90, Skill 65):
- Great culture fit but skill gaps
- **Strategy**: Hire if coachable + invest in training
- **Success Rate**: 70% with strong onboarding

**High Skill, Lower Culture** (e.g., Skill 95, Cultural 60):
- Excellent skills but culture mismatch
- **Strategy**: Assess culture adaptability carefully
- **Success Rate**: 50% - high risk of early turnover

**Balanced Match** (All dimensions 75-85):
- Solid all-around candidate
- **Strategy**: Standard hiring process
- **Success Rate**: 80%+

**"Rising Star"** (Performance 95+, others 70-80):
- High potential for growth
- **Strategy**: Invest in development
- **Success Rate**: 85%+

---

## Team Compatibility Assessment

### Team Health Metrics

**Team Health Score (0-100)**:
- **85-100**: High-performing team - excellent collaboration, strong output
- **70-84**: Healthy team - good dynamics, minor friction
- **55-69**: Functional team - gets work done but room for improvement
- **<55**: Struggling team - collaboration issues, morale problems

**Psychological Safety Score (0-100)**:
- **85-100**: Very safe - team speaks freely, takes risks, admits mistakes
- **70-84**: Safe - mostly comfortable, some hesitation
- **55-69**: Moderate - selective sharing, political awareness
- **<55**: Unsafe - fear of speaking up, blame culture

**Collaboration Score (0-100)**:
- **85-100**: Excellent - seamless teamwork, strong cross-functional ties
- **70-84**: Good - effective collaboration with occasional silos
- **55-69**: Fair - collaboration exists but could improve
- **<55**: Poor - siloed, minimal cross-team work

### Ideal Candidate Profile

**Required Traits** (Must-haves):
- Skills/experience the team lacks
- Work style that complements team
- Traits that fill leadership/creativity/analytical gaps

**Skill Gaps to Fill**:
- Technical skills missing on team
- Domain expertise needed
- Soft skills underrepresented

### Team Fit Assessment

Input candidate data:
- Skills (comma-separated)
- Work style preferences
- Personality traits

Output:
- **Team Fit Score**: How well candidate integrates (0-100)
- **Fit Level**: Excellent / Strong / Moderate / Weak
- **Compatibility Strengths**: What candidate brings to team
- **Integration Concerns**: Potential friction points
- **Integration Prediction**: AI assessment of onboarding success

### Using Team Fit Scores

**Excellent Fit (85-100)**:
- Fills critical gaps
- Complements team dynamics
- High integration probability
- **Action**: Prioritize hire

**Strong Fit (70-84)**:
- Good addition to team
- Minor gaps or overlaps
- Likely smooth integration
- **Action**: Proceed if other factors align

**Moderate Fit (55-69)**:
- Some compatibility concerns
- May need extra support
- Integration uncertain
- **Action**: Assess team's capacity to onboard

**Weak Fit (<55)**:
- Significant team mismatch
- High friction risk
- Difficult integration predicted
- **Action**: Consider different team or role

---

## Intelligent Resume Analysis

### Overview

The Resume Analysis feature provides semantic understanding of candidate resumes beyond keyword matching, identifying transferable skills, validating achievements, and analyzing career progression patterns.

### API Endpoint

**POST** `/api/scout/analyze-resume`

**Rate Limit**: 30 requests per minute

**Request Body**:
```json
{
  "resume_data": {
    "name": "Jane Smith",
    "summary": "Experienced software engineer with 8 years in full-stack development...",
    "skills": ["Python", "React", "AWS", "Leadership", "Agile"],
    "experience": [
      {
        "title": "Senior Software Engineer",
        "company": "Tech Corp",
        "start_date": "2020-01",
        "end_date": "2025-11",
        "description": "Led team of 5 engineers..."
      }
    ],
    "education": [
      {
        "degree": "Bachelor of Science in Computer Science",
        "institution": "State University",
        "field": "Computer Science",
        "year": 2016,
        "honors": "Cum Laude"
      }
    ],
    "achievements": [
      "Increased system performance by 40%",
      "Mentored 12 junior developers"
    ]
  },
  "job_id": 123  // Optional: for job-specific analysis
}
```

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "resume_analysis": {
      "semantic_skills": {
        "explicit_skills": [...],
        "transferable_skills": [...],
        "domain_expertise_depth": 85,
        "skill_diversity_score": 78
      },
      "career_progression": {
        "pattern_type": "Ambitious Achiever",
        "ambition_score": 82,
        "stability_score": 75,
        "career_narrative": "..."
      },
      "achievement_validation": {
        "quantified_achievements": [...],
        "exceptional_indicators": [...]
      },
      "gap_transition_analysis": {...},
      "red_flags": {...},
      "cultural_dna_alignment": {...},
      "transferable_skills_matrix": {...},
      "overall_assessment": {
        "overall_match_score": 84,
        "recommendation": "STRONG HIRE - Top Candidate",
        "top_strengths": [...],
        "interview_focus_areas": [...]
      },
      "experience_metrics": {
        "total_years": 8.5,
        "average_tenure": 2.8,
        "promotions_count": 3
      },
      "candidate_archetype": "Ambitious Achiever",
      "analyzed_at": "2025-11-06T10:30:00Z"
    },
    "cached": false
  }
}
```

### 8 Analysis Sections Explained

1. **Semantic Skill Analysis**: Identifies explicit skills AND inferred transferable skills from experience descriptions
2. **Career Progression Pattern**: Classifies candidates into 7 archetypes (Visionary Leader, Innovative Catalyst, Ambitious Achiever, Domain Expert, Cultural Champion, Reliable Performer, Solid Contributor)
3. **Achievement Validation**: Cross-references achievements with industry benchmarks, estimates percentile ranking
4. **Gap & Transition Analysis**: Context-aware evaluation of employment gaps and career pivots
5. **Red Flag Detection**: Identifies concerns (job hopping, inconsistencies) while remaining fair and contextual
6. **Cultural DNA Alignment**: Scores alignment with company values (requires DNA profile)
7. **Transferable Skills Matrix**: Maps skills across industries/roles (e.g., leadership from non-management positions)
8. **Overall Assessment**: Weighted score, hiring recommendation, interview guidance

### Candidate Archetypes

- **Visionary Leader** (Ambition 80+, Leadership progression): C-suite potential
- **Innovative Catalyst** (Innovation 80+, Cultural fit 70+): Change agents, product innovators
- **Ambitious Achiever** (Ambition 75+, Cultural fit 75+): High performers, rapid growth trajectory
- **Domain Expert** (Specialist progression): Deep technical expertise, thought leaders
- **Cultural Champion** (Cultural alignment 80+): Values ambassadors, culture carriers
- **Reliable Performer** (Steady progression): Consistent contributors, low-risk hires
- **Solid Contributor** (Baseline): Meets requirements, stable performer

### Token Usage & Cost Estimates

- **Average tokens per analysis**: ~1,300 tokens (prompt) + ~700 tokens (response) = 2,000 total
- **GPT-4o cost**: ~$0.03 per resume analysis
- **Caching**: 7-day TTL to reduce costs for repeat analyses
- **Monthly estimate** (100 resumes/month): ~$3 in OpenAI costs

### Best Practices

**Minimum Resume Data Required**:
- Name (required)
- At least 1 experience entry OR education entry
- Skills array (recommended)

**When to Use `job_id` Parameter**:
- Include when analyzing for specific position
- Enables job-specific skill matching
- Provides tailored interview questions
- Omit for general candidate evaluation

**Interpreting Scores**:
- **85+**: Strong hire, top 10% candidate
- **75-84**: Recommended, good fit
- **65-74**: Acceptable, conditional hire
- **<65**: Below threshold, additional screening needed

**Cache Behavior**:
- 7-day cache based on full resume content hash
- Updates automatically if resume data changes
- Check `cached` flag in response
- Clear cache: `Cache::forget('resume_analysis_' . md5($prompt))`

### Troubleshooting

**Low Scores Despite Good Resume**:
- Check if company DNA profile exists (cultural alignment requires it)
- Verify skills are spelled correctly (case-insensitive matching)
- Ensure experience dates are in YYYY-MM format
- Review GPT-4 response for detailed reasoning

**Parsing Errors**:
- Experience/education must be valid JSON arrays
- Dates should be strings in ISO format or "YYYY-MM"
- Skills should be comma-separated or array format

**GPT-4 Failures**:
- Check OpenAI API key is valid
- Verify rate limits not exceeded (TPM: 10,000)
- Review logs for specific error messages
- System falls back to cached data if available

---

## Multi-Stage Automated Shortlisting

### Overview

S.C.O.U.T.'s automated shortlisting implements a sophisticated 4-round evaluation pipeline that mimics and enhances human decision-making, processing large applicant pools efficiently while maintaining fairness and consistency.

### Evaluation Pipeline

#### Round 1: Basic Qualification Screening
**Purpose**: Eliminate clearly unqualified candidates, ensure legal compliance

**Pass Threshold**: 60 points

**Evaluation Criteria**:
- ✅ Education verification (degree hierarchy: High School→Associate→Bachelor→Master→PhD)
- ✅ Minimum experience threshold (e.g., 5 years required)
- ✅ Work authorization (legal requirement, hard fail if missing)
- ✅ Location compatibility (considers remote options)
- ✅ Required certifications (PMP, AWS, etc.)

**Scoring Deductions**:
- Missing education: -40 points (hard fail if strict requirement)
- Experience gap: -10 points per year short (hard fail if 3+ years)
- No work authorization: -50 points (hard fail)
- Location mismatch: -25 points (hard fail if not remote)
- Missing certifications: -15 points each (hard fail if 3+ missing)

**Example Rejection Reason**:
> "Does not meet minimum education requirement: Bachelor's Degree. Below minimum experience: 2 years (requires 5). Missing certifications: PMP, Scrum Master."

#### Round 2: Skills & Competency Matching
**Purpose**: Evaluate technical and soft skills against role requirements

**Pass Threshold**: 65 points

**Weighted Scoring**:
- Required skills match: 60% (critical for role)
- Preferred skills match: 20% (nice-to-haves)
- Success trait alignment: 20% (based on company DNA)

**Additional Factors**:
- Soft skills evaluation: 30% of total Round 2 score
- Keywords: leadership, communication, teamwork, problem-solving, adaptability, creativity

**Example Rejection Reason**:
> "Missing key skills: Kubernetes, Terraform, CI/CD. Overall skills competency below threshold (58/100)."

#### Round 3: Cultural Fit Assessment
**Purpose**: Ensure alignment with organizational values and work style

**Pass Threshold**: 60 points (flexible - culture can be taught)

**Weighted Scoring**:
- Value alignment: 40% (core values match)
- Work style compatibility: 30% (remote/hybrid, collaboration style)
- Communication style: 20% (analyzed from cover letter)
- Team dynamics prediction: 10% (integration likelihood)

**Requires**: Company DNA Profile (falls back to baseline 70 score if unavailable)

**Example Rejection Reason**:
> "Limited value alignment with company culture (45/100). Work style may not align with company preferences."

#### Round 4: Potential & Growth Analysis
**Purpose**: Identify candidates with long-term value and growth potential

**Pass Threshold**: 55 points (bonus round - identifies high-potential candidates)

**Weighted Scoring**:
- Learning agility: 40% (continuous education, diverse experience)
- Career trajectory: 35% (promotions, title progression)
- Future potential: 25% (succession planning viability)

**Learning Agility Indicators**:
- Recent education/certifications (within 3 years): +10-20 points
- Diverse experience across companies: +3 points per company
- Professional certifications: +5 points each

**Career Trajectory Indicators**:
- Promotion to Senior role: +15 points
- Promotion to Lead/Manager: +20 points
- Consistent growth pattern: +15 points

**Example Rejection Reason**:
> "Limited growth potential for long-term value (52/100). No evidence of recent skill development."

### Overall Scoring

**Weighted Formula**:
```
Overall Score = (Round1 × 0.15) + (Round2 × 0.35) + (Round3 × 0.30) + (Round4 × 0.20)
```

**Recommendations**:
- **85+**: STRONG HIRE - Top Candidate
- **75-84**: RECOMMEND - Good Fit
- **65-74**: CONSIDER - Acceptable Candidate
- **<65**: DO NOT SHORTLIST

### API Endpoint

**POST** `/api/scout/shortlist`

**Rate Limit**: 20 requests per minute

**Request Body**:
```json
{
  "job_id": 456,
  "application_ids": [101, 102, 103, 104, 105]
}
```

**Response Structure**:
```json
{
  "success": true,
  "shortlisting_results": {
    "total_applications": 5,
    "round_1_passed": 4,
    "round_2_passed": 3,
    "round_3_passed": 2,
    "round_4_passed": 2,
    "shortlisted": [
      {
        "application_id": 101,
        "candidate_name": "John Doe",
        "overall_score": 87.5,
        "recommendation": "STRONG HIRE - Top Candidate",
        "round_scores": {
          "round_1": 95,
          "round_2": 88,
          "round_3": 82,
          "round_4": 85
        },
        "strengths": [
          "Meets education requirement",
          "8 years of experience (exceeds minimum)",
          "Strong match on required skills (9/10)",
          "Strong alignment with company values",
          "High learning agility - quick to adapt"
        ],
        "concerns": []
      }
    ],
    "rejected_by_round": {
      "round_1": [
        {
          "application_id": 105,
          "candidate_name": "Bob Wilson",
          "rejected_at_round": 1,
          "score": 45,
          "reason": [
            "Does not meet minimum education requirement: Bachelor's Degree",
            "Below minimum experience: 2 years (requires 5)"
          ]
        }
      ],
      "round_2": [...],
      "round_3": [...],
      "round_4": [...]
    },
    "processing_time": 12.4
  },
  "summary": {
    "total_evaluated": 5,
    "shortlisted": 2,
    "funnel": {
      "round_1_passed": 4,
      "round_2_passed": 3,
      "round_3_passed": 2,
      "round_4_passed": 2
    },
    "processing_time_seconds": 12.4
  }
}
```

### Background Job Processing

For large batches (50+ applications), use the queue:

```php
use App\Jobs\AutomatedShortlistingJob;

// Dispatch to queue
AutomatedShortlistingJob::dispatch($jobId, $applicationIds, $userId);

// Track progress via cache
$cacheKey = "shortlisting_progress_{$jobId}_" . md5(implode(',', $applicationIds));
$progress = Cache::get($cacheKey);
// Returns: ['status' => 'processing', 'progress' => 45, 'total' => 100]
```

**Job Configuration**:
- **Tries**: 3 attempts with exponential backoff (1min, 5min, 15min)
- **Timeout**: 600 seconds (10 minutes)
- **Queue**: Default queue, priority medium
- **Tags**: `shortlisting`, `job:{jobId}`, `applications:{count}`

**Automatic Status Updates**:
- Shortlisted applications → Status: `shortlisted`, `shortlisted_at` timestamp
- Rejected applications → Status: `rejected`, `rejection_reason` includes round info

**Notifications**:
- Email sent to requester upon completion
- Includes shortlist count, total evaluated, processing time
- Failure notifications on permanent job failure

### Best Practices

**Optimal Batch Sizes**:
- Real-time (synchronous): 1-20 applications
- Background job (queued): 20-500 applications
- Split into multiple jobs: 500+ applications

**When to Run Shortlisting**:
- ✅ After job posting closes (batch all applications)
- ✅ Weekly for ongoing positions (new applications only)
- ✅ On-demand for urgent hiring (specific candidates)
- ❌ Don't run multiple times on same candidates (wastes resources)

**Interpreting Results**:
- **High Round 1 rejection**: Review job requirements (too strict?)
- **High Round 2 rejection**: Skills mismatch (wrong sourcing channels?)
- **High Round 3 rejection**: Culture misalignment (clearer job descriptions needed?)
- **High Round 4 rejection**: Low potential pool (acceptable for entry-level roles)

**Fairness Considerations**:
- Gap analysis is context-aware (doesn't penalize career breaks unfairly)
- Red flag detection considers circumstances (job changes due to company issues)
- Cultural fit avoids bias (focuses on work style, not demographics)
- Minimum thresholds prevent false rejections

### Dashboard Features

**Interactive UI** (`/scout/automated-shortlisting`):
- 📊 **Pipeline Overview**: Visual 4-round evaluation stages
- ⚙️ **Configuration Panel**: Job selection, application selection (all vs. specific)
- 📈 **Funnel Metrics**: Pass counts per round with color-coded cards
- 🏆 **Shortlisted Candidates**: Ranked cards with scores, recommendations, strengths/concerns
- ❌ **Rejection Details**: By-round breakdown with specific reasons
- ⏱️ **Processing Time**: Performance metrics

**Export Options**:
```bash
# Export shortlist to CSV
php artisan scout:export-shortlist {jobId} --format=csv

# Export full results (including rejections) to JSON
php artisan scout:export-shortlist {jobId} --format=json --include-rejections
```

### Performance Optimization

**Caching Strategy**:
- Candidate profiles cached per application (1 hour TTL)
- Company DNA profiles cached (24 hour TTL)
- Match scores cached (7 day TTL)

**Database Optimization**:
- Eager load relationships: `with(['user.profile', 'job.company.dnaProfile'])`
- Index on: `applications.status`, `applications.job_id`, `applications.created_at`
- Batch updates for status changes (single query vs. N queries)

**Cost Estimates**:
- No GPT-4 calls (all logic-based evaluation)
- Average processing time: 0.25 seconds per candidate
- 100 applications: ~25 seconds total
- Database queries: ~15 per candidate (optimized with eager loading)

### Troubleshooting

**All Candidates Rejected at Round 1**:
- Verify job requirements are realistic
- Check education/experience thresholds in job model
- Review `required_certifications` field (typos?)

**No Cultural Fit Scores**:
- Company DNA profile must exist: `CompanyDNAProfile::where('company_id', $id)->exists()`
- Run DNA analysis first: `POST /api/scout/analyze-dna`

**Slow Processing (>1 sec per candidate)**:
- Check database indexes: `php artisan db:show-indexes applications`
- Verify eager loading is active (no N+1 queries)
- Review Telescope for slow queries

**Job Fails with Timeout**:
- Reduce batch size (max 100 per job)
- Increase timeout in job class: `public $timeout = 900;`
- Check queue worker is running: `php artisan queue:work`

---

## Privacy & Compliance

### GDPR Compliance

**Employee Data Collection**:
- ✅ Obtain explicit consent before DNA analysis
- ✅ Clearly state purpose: "Improve hiring decisions"
- ✅ Allow employees to opt-out of analysis
- ✅ Provide data access/export upon request
- ✅ Delete employee data upon request (right to be forgotten)

**Candidate Data Processing**:
- ✅ Include S.C.O.U.T. analysis in privacy policy
- ✅ Obtain consent during application process
- ✅ Store only necessary data for analysis
- ✅ Delete candidate data after 6 months (or per policy)

**Data Retention Policies**:
- **DNA Profiles**: Indefinitely (while company active)
- **Hiring Patterns**: 3 years
- **Success Indicators**: While employee employed + 1 year
- **Team Dynamics**: Current + 12 months historical
- **Candidate Match Scores**: 6 months after application

**Right to Deletion**:
```bash
# Delete employee from S.C.O.U.T. analysis
php artisan scout:delete-employee {userId}

# Delete candidate match data
php artisan scout:delete-candidate {userId}

# Export user data for GDPR request
php artisan scout:export-user-data {userId}
```

### Data Security

**Encryption**:
- Database fields with sensitive data encrypted at rest
- API responses over HTTPS/TLS only
- API tokens hashed in database

**Access Control**:
- Employer-only middleware on all S.C.O.U.T. endpoints
- Company ownership validation on every request
- Role-based permissions (admin, hr_manager, recruiter)

**Audit Logging**:
```php
// All S.C.O.U.T. actions logged
Log::info('DNA analysis accessed', [
    'company_id' => $companyId,
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'timestamp' => now()
]);
```

### Ethical Considerations

**Bias Mitigation**:
- ⚠️ AI models may reflect historical biases
- ✅ Regularly audit for disparate impact
- ✅ Use scores as one factor, not sole decision
- ✅ Human review required for hiring decisions

**Transparency**:
- Candidates informed of AI-assisted evaluation
- Employers understand scoring methodology
- Recommendations explained, not black-box

**Fairness**:
- Scores based on job-relevant factors only
- No discrimination on protected characteristics
- Regular validation against actual hire outcomes

---

## API Rate Limiting

### Endpoint Rate Limits

**Configured in `routes/api.php`**:

| Endpoint | Limit | Window | Reasoning |
|----------|-------|--------|-----------|
| `POST /api/scout/analyze-dna` | 10/min | Company | CPU-intensive, prevents abuse |
| `GET /api/scout/dna-profile` | 60/min | User | Read-only, higher limit |
| `POST /api/scout/analyze-hiring-patterns` | 20/min | Company | Moderate cost |
| `POST /api/scout/predict-candidate-success` | 60/min | User | High-frequency use |
| `POST /api/scout/team-compatibility` | 60/min | User | Frequently assessed |
| `GET /api/scout/candidate-match/{id}` | 60/min | User | Cached responses |
| `GET /api/scout/culture-fit-criteria` | 60/min | User | Read-only |
| `GET /api/scout/hiring-insights` | 60/min | User | Dashboard view |

### Rate Limit Headers

Responses include:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1699276800
```

### 429 Response

When rate limit exceeded:
```json
{
  "success": false,
  "message": "Too many requests. Please try again in 60 seconds.",
  "retry_after": 60
}
```

### Request Rate Limit Increase

For enterprise customers:
1. Contact support: scout-support@studai.com
2. Provide use case justification
3. Upgrade to Enterprise tier
4. Custom limits applied per company

---

## Monitoring & Alerting

### Key Metrics to Track

**DNA Analysis**:
- Analysis completion rate
- Average analysis duration
- Confidence score distribution
- DNA health score trends
- Error rate

**Candidate Matching**:
- Matches generated per day
- Average match score
- Score accuracy (compare predictions to actual hires)
- Processing time per candidate
- Cache hit rate

**Queue Performance**:
- Job throughput (jobs/hour)
- Average job duration
- Failed job rate
- Queue depth
- Worker CPU/memory usage

**API Performance**:
- Response time (p50, p95, p99)
- Error rate (4xx, 5xx)
- Rate limit hits
- Concurrent requests

### Logging

**Application Logs** (`storage/logs/laravel.log`):
```
[2025-11-06 03:00:15] INFO: Starting DNA analysis [company_id=42]
[2025-11-06 03:02:47] INFO: DNA analysis completed [company_id=42, health_score=87]
```

**Queue Logs** (`storage/logs/worker.log`):
```
[2025-11-06 04:00:00] Processing: GenerateCandidateMatchScoresJob
[2025-11-06 04:12:35] Processed: GenerateCandidateMatchScoresJob
```

**Error Logs**:
```
[2025-11-06 10:15:22] ERROR: DNA analysis failed [company_id=42, error=Insufficient data]
```

### Recommended Monitoring Tools

**Laravel Telescope** (Development):
- View all queries, jobs, requests
- Performance profiling
- Exception tracking

**Laravel Horizon** (Production):
- Queue monitoring dashboard
- Job throughput metrics
- Failed job management
- Worker supervision

**External Services**:
- **Sentry**: Exception tracking and alerting
- **Datadog**: Application performance monitoring
- **New Relic**: Full-stack observability
- **CloudWatch**: AWS infrastructure monitoring

### Alert Conditions

Set up alerts for:
- ❌ Failed job rate >5% (1 hour)
- ❌ Queue depth >100 jobs (15 minutes)
- ❌ API error rate >1% (5 minutes)
- ❌ Response time p95 >2 seconds (10 minutes)
- ⚠️ OpenAI API errors (any occurrence)
- ⚠️ DNA confidence score <60 (after analysis)
- ⚠️ Worker memory usage >80% (5 minutes)

---

## Testing Checklist

### Pre-Deployment Testing

**Unit Tests**:
```bash
php artisan test --filter Scout
```

- [ ] CorporateDNADecoderService::analyzeCompanyDNA()
- [ ] HiringPatternAnalyzerService::analyzeHiringPatterns()
- [ ] TeamDynamicsAnalyzerService::analyzeTeamDynamics()
- [ ] SuccessPredictorService::predictCandidateSuccess()
- [ ] All model relationships working
- [ ] Scopes return correct results

**Integration Tests**:
- [ ] Full DNA analysis flow with sample data
- [ ] Candidate match score generation
- [ ] Team compatibility assessment
- [ ] Hiring pattern analysis
- [ ] Queue jobs execute successfully

**API Tests**:
```bash
php artisan test --filter ScoutControllerTest
```

- [ ] All 8 endpoints return 200 on valid requests
- [ ] Unauthorized access returns 401
- [ ] Wrong company access returns 403
- [ ] Rate limiting triggers 429
- [ ] Validation errors return 422
- [ ] Response structure matches API spec

**Performance Tests**:
- [ ] DNA analysis completes <5 minutes (100 employees)
- [ ] Candidate scoring <3 seconds per candidate
- [ ] API response time <1 second (p95)
- [ ] Queue processes 10+ jobs/minute
- [ ] Database queries optimized (no N+1)

**Browser Tests**:
```bash
php artisan dusk --filter Scout
```

- [ ] DNA Dashboard loads and displays data
- [ ] Culture Analysis form submits
- [ ] Candidate Matching search works
- [ ] Team Compatibility assessment functional
- [ ] Hiring Insights charts render
- [ ] Mobile responsive on all views

### Post-Deployment Validation

**Day 1**:
- [ ] First DNA analysis completes successfully
- [ ] Queue workers running (check Supervisor)
- [ ] No errors in logs
- [ ] API accessible and responding
- [ ] Notifications sent correctly

**Week 1**:
- [ ] Weekly DNA analysis scheduled and executed
- [ ] Daily candidate scoring running
- [ ] Employer feedback on accuracy
- [ ] Performance metrics within targets
- [ ] No critical issues reported

**Month 1**:
- [ ] Monthly team dynamics refresh completed
- [ ] OpenAI costs within budget
- [ ] Match score accuracy validated (compare to actual hires)
- [ ] User adoption metrics reviewed
- [ ] Feature requests documented

---

## Troubleshooting

### Low Confidence Scores (<60)

**Symptoms**:
- DNA analysis shows confidence score below 60
- Warning message: "Insufficient data"

**Causes**:
- Fewer than 5 employees with profiles
- Fewer than 5 historical hires
- Missing mission/vision statements
- Incomplete employee profiles

**Solutions**:
1. Add more employee profiles:
   ```bash
   php artisan scout:employee-count {companyId}
   ```
2. Import historical hires (CSV or HRIS sync)
3. Complete company profile (mission, vision, values)
4. Ensure employee profiles >80% complete
5. Re-run analysis:
   ```bash
   php artisan scout:analyze-dna {companyId} --force
   ```

### Pattern Analysis Failures

**Symptoms**:
- Hiring patterns not updating after hire
- Error in logs: "Pattern analysis failed"

**Causes**:
- Total hires below minimum threshold (5)
- Missing application data (source, dates)
- Invalid hire status in database

**Solutions**:
1. Check total hires:
   ```bash
   php artisan scout:hire-count {companyId}
   ```
2. Verify application data completeness
3. Manually trigger pattern update:
   ```bash
   php artisan scout:refresh-patterns {companyId}
   ```
4. Check job logs:
   ```bash
   php artisan queue:failed
   ```

### Slow API Responses

**Symptoms**:
- API endpoints taking >3 seconds
- Timeouts on dashboard

**Causes**:
- Missing database indexes
- Cache not working
- Too many OpenAI calls
- Inefficient queries (N+1 problem)

**Solutions**:
1. Enable query logging:
   ```php
   DB::enableQueryLog();
   ```
2. Check for missing indexes:
   ```sql
   EXPLAIN SELECT * FROM company_dna_profiles WHERE company_id = 42;
   ```
3. Verify Redis cache working:
   ```bash
   php artisan cache:clear
   redis-cli ping
   ```
4. Review Telescope for slow queries
5. Implement eager loading:
   ```php
   CompanyDNAProfile::with(['company', 'cultureAnalysis'])->find($id);
   ```

### Queue Jobs Stuck

**Symptoms**:
- Jobs in queue but not processing
- Worker logs show no activity

**Causes**:
- Workers not running
- Redis connection issue
- Job timeout too low
- Memory exhaustion

**Solutions**:
1. Check worker status:
   ```bash
   sudo supervisorctl status studai-worker:*
   ```
2. Restart workers:
   ```bash
   sudo supervisorctl restart studai-worker:*
   ```
3. Verify Redis connection:
   ```bash
   php artisan queue:monitor
   ```
4. Increase timeout in job class:
   ```php
   public $timeout = 900; // 15 minutes
   ```
5. Check memory usage:
   ```bash
   php artisan horizon:pause
   php artisan horizon:continue
   ```

### OpenAI API Errors

**Symptoms**:
- Analysis fails with "OpenAI API error"
- 429 rate limit errors

**Causes**:
- Invalid API key
- Insufficient credits
- Rate limit exceeded (60 requests/min)
- Model unavailable

**Solutions**:
1. Verify API key:
   ```bash
   php artisan tinker
   >>> config('services.openai.api_key')
   ```
2. Check OpenAI billing: https://platform.openai.com/account/billing
3. Implement retry logic (already in services)
4. Add delays between requests:
   ```php
   sleep(1); // 1 second delay
   ```
5. Use response caching (already implemented)

---

## Rollback Plan

### Quick Disable (No Data Loss)

**Step 1**: Disable S.C.O.U.T. feature flag
```env
SCOUT_ENABLED=false
```

**Step 2**: Clear configuration cache
```bash
php artisan config:clear
php artisan cache:clear
```

**Step 3**: Stop queue workers
```bash
sudo supervisorctl stop studai-worker:*
```

**Result**: S.C.O.U.T. disabled, all data preserved, can re-enable anytime.

### Full Rollback (Remove System)

**Step 1**: Stop all S.C.O.U.T. jobs
```bash
sudo supervisorctl stop studai-worker:*
php artisan queue:clear redis
```

**Step 2**: Remove scheduled tasks
Edit `routes/console.php` and comment out S.C.O.U.T. schedules.

**Step 3**: Backup S.C.O.U.T. data (if needed)
```bash
php artisan scout:export-all-data > scout_backup_$(date +%Y%m%d).json
```

**Step 4**: Rollback migration
```bash
php artisan migrate:rollback --step=1
```

**Step 5**: Remove code files
```bash
rm -rf app/Services/AI/Scout/
rm -rf app/Jobs/AnalyzeCompanyDNAJob.php
rm -rf app/Jobs/UpdateHiringPatternsJob.php
rm -rf app/Jobs/GenerateCandidateMatchScoresJob.php
rm -rf app/Jobs/RefreshTeamDynamicsJob.php
rm -rf app/Http/Controllers/ScoutController.php
rm -rf resources/views/scout/
```

**Step 6**: Clear routes and cache
```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

**Step 7**: Restart workers
```bash
sudo supervisorctl start studai-worker:*
```

### Data Recovery

If rollback was accidental:

**Step 1**: Restore from backup
```bash
php artisan migrate # Re-create tables
php artisan scout:import-data scout_backup_20251106.json
```

**Step 2**: Re-enable feature flag
```env
SCOUT_ENABLED=true
```

**Step 3**: Restart workers
```bash
sudo supervisorctl restart studai-worker:*
```

---

## Support & Contact

**Documentation**: https://docs.studai.com/scout  
**Support Email**: scout-support@studai.com  
**Enterprise Support**: enterprise@studai.com  
**Status Page**: https://status.studai.com

**Response Times**:
- Critical (system down): 1 hour
- High (feature broken): 4 hours
- Medium (performance issue): 24 hours
- Low (question): 48 hours

---

**End of Deployment Guide**  
*Last updated: November 6, 2025*
