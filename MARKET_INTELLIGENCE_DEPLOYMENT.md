# Market Intelligence & Positioning System - Deployment Guide

## 📋 System Overview

The Market Intelligence & Positioning System is a comprehensive AI-powered feature that analyzes millions of job postings, salary reports, and industry trends to provide users with real-time market insights. It delivers percentile rankings, competitive analysis, salary intelligence, and skill trend analysis.

### Key Features

1. **Real-Time Market Overview**
   - Market health score (0-100)
   - Trending roles with growth percentages
   - Top hiring locations with average salaries
   - Emerging skills identification
   - Remote work trends

2. **Personalized Market Positioning**
   - Market readiness score (0-100) based on 5 components:
     - Profile completeness (15%)
     - Experience quality (30%)
     - Skills modernity (25%)
     - Education relevance (10%)
     - Market alignment (20%)
   - Percentile rankings: overall, experience, skills, compensation
   - Competitive advantages and weaknesses analysis
   - Skill gap identification with priority levels
   - Role fit analysis (best fit, trending roles, roles to avoid)

3. **Advanced Salary Intelligence**
   - User salary percentile positioning
   - Market median comparison
   - Month-over-month and year-over-year trends
   - 6-month and 12-month salary predictions with confidence scores
   - Multi-city salary comparison tool
   - Negotiation insights calculator with target ranges and talking points

4. **Skill Trend Analysis**
   - Individual skill analysis: demand score, growth rate, value score
   - 12-month skill evolution tracking
   - Obsolescence risk assessment
   - Trending skills categorization (emerging, hot, declining)
   - High-value skill combinations
   - Personalized upskilling roadmap (3 phases: 0-3m, 3-6m, 6-12m)

5. **Role Predictions**
   - Emerging roles identification
   - Dying positions to avoid
   - Role demand forecasting
   - Career path recommendations

6. **Weekly Insights Digest**
   - Personalized email with readiness score changes
   - Market shifts affecting user's profile
   - New skill recommendations
   - Competitive positioning updates

---

## 🏗️ Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
├─────────────────────────────────────────────────────────────┤
│  Web Views (Blade Templates)                                │
│  • market/overview.blade.php                                │
│  • market/positioning.blade.php                             │
│  • market/salary-intelligence.blade.php                     │
│  • market/skill-trends.blade.php                            │
│                                                              │
│  Chart.js 4.4.0 • Responsive Design • Real-time Updates     │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                     API LAYER                                │
├─────────────────────────────────────────────────────────────┤
│  MarketIntelligenceController (10 endpoints)                │
│  • GET  /api/market/overview                                │
│  • GET  /api/market/user-position                           │
│  • GET  /api/market/salary-insights                         │
│  • POST /api/market/negotiation-insights                    │
│  • GET  /api/market/skill-trends                            │
│  • GET  /api/market/skill-combinations                      │
│  • GET  /api/market/upskilling-roadmap                      │
│  • GET  /api/market/role-predictions                        │
│  • GET  /api/market/competitive-analysis                    │
│  • GET  /api/market/recommendations                         │
│                                                              │
│  Middleware: auth:sanctum • Rate Limiting • Validation      │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                   SERVICE LAYER                              │
├─────────────────────────────────────────────────────────────┤
│  AI Services (4 core services)                              │
│  • MarketIntelligenceService    - Market-wide analysis      │
│  • MarketPositioningService     - User positioning          │
│  • SalaryIntelligenceService    - Salary intelligence       │
│  • SkillTrendAnalysisService    - Skill analysis            │
│                                                              │
│  Features: OpenAI GPT-4 • Redis Caching • Error Handling   │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                   DATA LAYER                                 │
├─────────────────────────────────────────────────────────────┤
│  Eloquent Models (6 models)                                 │
│  • MarketDataSnapshot      - Market snapshots               │
│  • UserMarketPosition      - User positioning data          │
│  • SalaryTrend            - Salary trends                   │
│  • SkillTrend             - Skill trends                    │
│  • RolePrediction         - Role predictions                │
│  • CompetitiveBenchmark   - Competitive analysis            │
│                                                              │
│  Database: MySQL • JSON columns • Composite indexes         │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                BACKGROUND PROCESSING                         │
├─────────────────────────────────────────────────────────────┤
│  Queued Jobs (4 jobs)                                       │
│  • UpdateMarketDataJob        (Hourly)                      │
│  • AnalyzeTrendsJob          (Daily at 1 AM)               │
│  • UpdateUserPositioningJob  (Daily at 3 AM)               │
│  • GenerateInsightsJob       (Weekly Sunday 6 AM)          │
│                                                              │
│  Queue: Redis • Horizon • Retry Logic • Timeouts            │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                 EXTERNAL INTEGRATIONS                        │
├─────────────────────────────────────────────────────────────┤
│  • OpenAI GPT-4 API    - AI insights generation            │
│  • Job Board Scrapers  - Market data collection            │
│  • Salary APIs         - Compensation data                  │
│  • Email Service       - Weekly digest notifications        │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Hourly Market Data Update**
   - `UpdateMarketDataJob` runs every hour
   - Aggregates job postings, salaries, skill demand
   - Stores snapshot in `market_data_snapshots` table
   - Caches market overview for 1 hour

2. **Daily Trend Analysis**
   - `AnalyzeTrendsJob` runs daily at 1 AM
   - Analyzes top 50 roles × top 10 locations for salary trends
   - Analyzes top 100 skills for demand trends
   - Generates role predictions with AI insights
   - Updates `salary_trends`, `skill_trends`, `role_predictions` tables

3. **Daily User Positioning**
   - `UpdateUserPositioningJob` runs daily at 3 AM
   - Iterates all active job seekers
   - Calculates readiness score and percentile rankings
   - Generates competitive analysis and recommendations
   - Updates `user_market_positions` and `competitive_benchmarks` tables

4. **Weekly Insights Generation**
   - `GenerateInsightsJob` runs weekly (Sunday 6 AM)
   - Detects market shifts (demand/salary changes >10%/5%)
   - Generates personalized insights per user
   - Sends `MarketInsightsDigestNotification` emails
   - Updates global insights in market snapshots

---

## 📊 Database Schema

### 1. market_data_snapshots
Stores hourly market data snapshots for trend analysis.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| data_date | date | Snapshot date |
| total_jobs | integer | Total job postings |
| avg_salary | decimal(12,2) | Average salary |
| market_health_score | decimal(5,2) | Market health (0-100) |
| demand_supply_ratio | decimal(8,4) | Demand/supply ratio |
| trending_roles | json | Top roles with growth % |
| top_locations | json | Top locations with avg salary |
| emerging_skills | json | Emerging skills list |
| remote_work_percentage | decimal(5,2) | Remote job percentage |
| ai_insights | json | AI-generated insights |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- Primary: `id`
- Composite: `data_date, created_at` (for historical queries)

---

### 2. user_market_positions
Stores each user's calculated market position and readiness score.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| calculated_at | datetime | Calculation timestamp |
| readiness_score | decimal(5,2) | Overall readiness (0-100) |
| profile_completeness_score | decimal(5,2) | Profile score (15% weight) |
| experience_quality_score | decimal(5,2) | Experience score (30% weight) |
| skills_modernity_score | decimal(5,2) | Skills score (25% weight) |
| education_relevance_score | decimal(5,2) | Education score (10% weight) |
| market_alignment_score | decimal(5,2) | Market score (20% weight) |
| percentile_overall | decimal(5,2) | Overall percentile |
| percentile_experience | decimal(5,2) | Experience percentile |
| percentile_skills | decimal(5,2) | Skills percentile |
| percentile_compensation | decimal(5,2) | Compensation percentile |
| competitive_advantages | json | Strengths list |
| competitive_weaknesses | json | Weaknesses list |
| skill_gaps | json | Missing skills with priority |
| role_fit | json | Best fit roles |
| recommendations | json | Personalized actions |
| status | enum | excellent/good/fair/below_market |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- Primary: `id`
- Foreign: `user_id` → users.id (on delete cascade)
- Composite: `user_id, calculated_at` (for historical tracking)
- Index: `readiness_score` (for percentile calculations)

---

### 3. salary_trends
Tracks salary trends by role, location, and experience level.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| role | varchar(255) | Job role title |
| location | varchar(255) | City/region |
| experience_level | varchar(50) | junior/mid/senior/lead |
| data_date | date | Trend date |
| sample_size | integer | Number of job postings |
| min_salary | decimal(12,2) | Minimum salary |
| max_salary | decimal(12,2) | Maximum salary |
| median_salary | decimal(12,2) | Median salary |
| avg_salary | decimal(12,2) | Average salary |
| percentile_25 | decimal(12,2) | 25th percentile |
| percentile_75 | decimal(12,2) | 75th percentile |
| percentile_90 | decimal(12,2) | 90th percentile |
| mom_change | decimal(8,4) | Month-over-month % change |
| yoy_change | decimal(8,4) | Year-over-year % change |
| trend_direction | enum | up/down/stable |
| prediction_6m | decimal(12,2) | 6-month forecast |
| prediction_12m | decimal(12,2) | 12-month forecast |
| confidence_score | decimal(5,2) | Prediction confidence (0-100) |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- Primary: `id`
- Composite: `role, location, experience_level, data_date` (for queries)
- Index: `data_date` (for trend analysis)

---

### 4. skill_trends
Tracks skill demand, growth, and value trends.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| skill_name | varchar(255) | Skill name |
| category | varchar(100) | Skill category |
| data_date | date | Trend date |
| demand_score | decimal(5,2) | Demand score (0-100) |
| growth_rate | decimal(8,4) | % growth rate |
| job_count | integer | Jobs requiring skill |
| avg_salary_premium | decimal(12,2) | Salary premium |
| value_score | decimal(5,2) | Value score (0-100) |
| trend_status | enum | emerging/hot/stable/declining/obsolete |
| obsolescence_risk | enum | low/medium/high |
| months_to_obsolescence | integer | Estimated months |
| learning_difficulty | enum | easy/medium/hard/expert |
| recommended_combinations | json | High-value skill pairs |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- Primary: `id`
- Composite: `skill_name, data_date` (for historical tracking)
- Index: `trend_status` (for filtering)
- Index: `demand_score` (for sorting)

---

### 5. role_predictions
Predicts role demand and emergence trends.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| role_title | varchar(255) | Job role title |
| category | varchar(100) | Role category |
| data_date | date | Prediction date |
| current_demand | integer | Current job count |
| predicted_demand_6m | integer | 6-month forecast |
| predicted_demand_12m | integer | 12-month forecast |
| growth_rate | decimal(8,4) | % growth rate |
| emergence_score | decimal(5,2) | Emergence score (0-100) |
| status | enum | emerging/growing/stable/declining/dying |
| avg_salary | decimal(12,2) | Average salary |
| required_skills | json | Top skills required |
| key_drivers | json | Growth drivers |
| rationale | text | AI-generated explanation |
| recommendation | enum | pursue/monitor/avoid |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- Primary: `id`
- Composite: `role_title, data_date` (for tracking)
- Index: `status` (for filtering)
- Index: `emergence_score` (for sorting)

---

### 6. competitive_benchmarks
Stores competitive analysis per user across categories.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| category | varchar(100) | Benchmark category |
| benchmark_date | date | Analysis date |
| user_value | varchar(255) | User's value |
| market_median | varchar(255) | Market median |
| percentile | decimal(5,2) | User's percentile |
| gap_analysis | text | Gap description |
| severity | enum | critical/high/medium/low |
| recommendation | text | Recommended action |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- Primary: `id`
- Foreign: `user_id` → users.id (on delete cascade)
- Composite: `user_id, benchmark_date` (for user benchmarks)
- Index: `category` (for filtering)

---

## 🔌 API Documentation

### Authentication

All Market Intelligence API endpoints require authentication via Laravel Sanctum:

```bash
# Include Bearer token in Authorization header
Authorization: Bearer {your-sanctum-token}
```

### Rate Limiting

API endpoints follow standard rate limiting policies configured in the application.

---

### 1. GET /api/market/overview

Get market-wide overview data.

**Authentication:** Required  
**Response:**

```json
{
  "market_health_score": 78.5,
  "total_jobs": 45632,
  "avg_salary": 95000,
  "demand_supply_ratio": 1.34,
  "remote_work_percentage": 42.3,
  "trending_roles": [
    {
      "role": "Senior Software Engineer",
      "count": 3450,
      "growth": 15.2,
      "avg_salary": 125000
    }
  ],
  "top_locations": [
    {
      "location": "San Francisco",
      "count": 8920,
      "avg_salary": 145000
    }
  ],
  "emerging_skills": [
    {
      "skill": "Rust",
      "demand_score": 85,
      "growth_rate": 127.3
    }
  ],
  "job_trends": [
    {
      "date": "2025-01",
      "count": 42000
    }
  ],
  "salary_distribution": [
    {
      "range": "50k-75k",
      "count": 8500
    }
  ]
}
```

---

### 2. GET /api/market/user-position

Get authenticated user's market position.

**Authentication:** Required  
**Response:**

```json
{
  "readiness_score": 76.5,
  "score_breakdown": {
    "profile_completeness": 85,
    "experience_quality": 72,
    "skills_modernity": 80,
    "education_relevance": 70,
    "market_alignment": 75
  },
  "percentiles": {
    "overall": 68,
    "experience": 72,
    "skills": 65,
    "compensation": 58
  },
  "competitive_advantages": [
    {
      "category": "Skills",
      "description": "Proficient in high-demand skill: React",
      "strength": "strong"
    }
  ],
  "competitive_weaknesses": [
    {
      "category": "Certifications",
      "description": "Missing AWS certification common in your field",
      "severity": "medium"
    }
  ],
  "skill_gaps": [
    {
      "skill": "Kubernetes",
      "priority": "high",
      "current_value": 0,
      "market_value": 92
    }
  ],
  "role_fit": {
    "best_fit": [
      {
        "role": "Frontend Developer",
        "match_score": 88,
        "rationale": "Strong React skills match requirements"
      }
    ],
    "trending": [
      {
        "role": "Full Stack Engineer",
        "growth_rate": 25.3,
        "avg_salary": 115000
      }
    ],
    "roles_to_avoid": [
      {
        "role": "jQuery Developer",
        "status": "declining",
        "growth_rate": -18.5
      }
    ]
  },
  "recommendations": [
    {
      "priority": "high",
      "action": "Learn Kubernetes to close critical skill gap",
      "impact": "Could increase percentile by 12 points"
    }
  ],
  "status": "good",
  "last_updated": "2025-01-05T03:00:00Z"
}
```

---

### 3. GET /api/market/salary-insights

Get salary intelligence for user's role/location.

**Authentication:** Required  
**Query Parameters:**
- `role` (optional): Specific role to analyze
- `location` (optional): Specific location
- `cities` (optional): Comma-separated cities for comparison

**Response:**

```json
{
  "user_salary_percentile": 62,
  "market_median": 95000,
  "user_current_salary": 88000,
  "difference_from_median": -7.37,
  "salary_trends": {
    "mom_change": 2.3,
    "yoy_change": 8.7,
    "trend_direction": "up"
  },
  "salary_movement": [
    {
      "date": "2024-12",
      "median": 92000
    }
  ],
  "predictions": {
    "6_month": {
      "predicted_salary": 98000,
      "confidence": 78
    },
    "12_month": {
      "predicted_salary": 102000,
      "confidence": 65
    }
  },
  "city_comparisons": [
    {
      "city": "San Francisco",
      "median_salary": 145000,
      "difference": 55.68
    }
  ]
}
```

---

### 4. POST /api/market/negotiation-insights

Calculate negotiation insights for job offer.

**Authentication:** Required  
**Request Body:**

```json
{
  "offered_salary": 105000,
  "role": "Software Engineer",
  "location": "Austin",
  "experience_years": 5
}
```

**Response:**

```json
{
  "offer_analysis": {
    "offered_salary": 105000,
    "market_percentile": 72,
    "recommendation": "Good offer, but room for negotiation"
  },
  "target_salary": {
    "minimum": 105000,
    "ideal": 115000,
    "stretch": 125000
  },
  "negotiation_talking_points": [
    {
      "point": "Market data shows 25% salary growth in this role over past 12 months",
      "strength": "strong",
      "category": "Market Trends"
    },
    {
      "point": "Your 5 years experience places you at 68th percentile",
      "strength": "medium",
      "category": "Experience"
    }
  ]
}
```

---

### 5. GET /api/market/skill-trends

Analyze specific skill or get trending skills.

**Authentication:** Required  
**Query Parameters:**
- `skill` (optional): Specific skill to analyze
- `status` (optional): Filter by status (emerging/hot/declining)
- `limit` (optional): Limit results (default 20)

**Response (specific skill):**

```json
{
  "skill": "React",
  "analysis": {
    "demand_score": 92,
    "growth_rate": 18.5,
    "value_score": 88,
    "trend_status": "hot"
  },
  "evolution": [
    {
      "date": "2025-01",
      "demand": 90,
      "value": 85
    }
  ],
  "obsolescence_risk": {
    "risk_level": "low",
    "risk_score": 15,
    "months_to_obsolescence": null
  }
}
```

**Response (trending list):**

```json
{
  "trending": {
    "emerging": [
      {
        "skill": "Rust",
        "demand_score": 75,
        "growth_rate": 127.3
      }
    ],
    "hot": [
      {
        "skill": "React",
        "demand_score": 92,
        "growth_rate": 18.5
      }
    ],
    "declining": [
      {
        "skill": "jQuery",
        "demand_score": 42,
        "growth_rate": -22.8
      }
    ]
  }
}
```

---

### 6. GET /api/market/skill-combinations

Get high-value skill combinations.

**Authentication:** Required  
**Query Parameters:**
- `limit` (optional): Limit results (default 10)

**Response:**

```json
{
  "combinations": [
    {
      "skill_1": "React",
      "skill_2": "TypeScript",
      "avg_salary": 125000,
      "premium": 15.2,
      "job_count": 3450
    }
  ]
}
```

---

### 7. GET /api/market/upskilling-roadmap

Get personalized upskilling roadmap.

**Authentication:** Required  
**Response:**

```json
{
  "current_skills_status": {
    "modern": ["React", "Node.js"],
    "stable": ["JavaScript", "CSS"],
    "declining": ["jQuery"]
  },
  "roadmap": {
    "immediate": [
      {
        "skill": "TypeScript",
        "rationale": "High demand (92), complements React",
        "difficulty": "medium",
        "estimated_months": 2
      }
    ],
    "short_term": [
      {
        "skill": "GraphQL",
        "rationale": "Emerging skill (growth: 85%), high value",
        "difficulty": "medium",
        "estimated_months": 3
      }
    ],
    "long_term": [
      {
        "skill": "Rust",
        "rationale": "Fastest growing (127%), future-proof",
        "difficulty": "hard",
        "estimated_months": 6
      }
    ]
  }
}
```

---

### 8. GET /api/market/role-predictions

Get role demand predictions.

**Authentication:** Required  
**Query Parameters:**
- `role` (optional): Specific role to analyze
- `status` (optional): Filter by status (emerging/growing/declining)
- `limit` (optional): Limit results (default 20)

**Response:**

```json
{
  "predictions": [
    {
      "role": "AI Engineer",
      "status": "emerging",
      "emergence_score": 92,
      "current_demand": 1250,
      "predicted_demand_6m": 1850,
      "predicted_demand_12m": 2600,
      "growth_rate": 108.5,
      "avg_salary": 155000,
      "required_skills": ["Python", "TensorFlow", "PyTorch"],
      "key_drivers": ["AI adoption", "Automation trends"],
      "rationale": "Rapid growth in AI/ML adoption across industries",
      "recommendation": "pursue"
    }
  ]
}
```

---

### 9. GET /api/market/competitive-analysis

Get user's competitive benchmarks.

**Authentication:** Required  
**Response:**

```json
{
  "benchmarks": [
    {
      "category": "Years of Experience",
      "user_value": "5 years",
      "market_median": "4 years",
      "percentile": 68,
      "gap_analysis": "Above median experience level",
      "severity": "low",
      "recommendation": "Leverage experience in negotiations"
    }
  ]
}
```

---

### 10. GET /api/market/recommendations

Get personalized recommendations.

**Authentication:** Required  
**Response:**

```json
{
  "recommendations": [
    {
      "priority": "high",
      "category": "Skills",
      "action": "Learn Kubernetes to close critical skill gap",
      "impact": "Could increase market percentile by 12 points",
      "timeline": "2-3 months"
    }
  ]
}
```

---

## 🌐 Web Interface

### Page 1: Market Overview (`/market/overview`)

**Purpose:** Market-wide intelligence dashboard  
**Access:** Authenticated users with verified email

**Sections:**
1. Market Health Score (0-100 gauge)
2. Key Metrics: Total jobs, avg salary, demand score, remote %
3. Trending Roles (top 5 with growth %)
4. Top Locations (top 5 with avg salary)
5. Emerging Skills (12 skill cards)
6. Job Trends Chart (6-month line chart)
7. Salary Distribution Chart (bar chart by range)

**Features:**
- Live data indicator (green pulse)
- Auto-refresh every 5 minutes
- Skeleton loading states
- Responsive grid layout

---

### Page 2: Your Market Position (`/market/positioning`)

**Purpose:** Personalized market positioning dashboard  
**Access:** Authenticated users with verified email

**Sections:**
1. Readiness Score Gauge (doughnut chart, 0-100)
2. Score Breakdown (5 progress bars with weights)
3. Percentile Rankings (4 cards: overall, experience, skills, compensation)
4. Competitive Advantages (green cards)
5. Competitive Weaknesses (orange cards)
6. Skill Gaps (priority badges: high/medium/low)
7. Role Fit Analysis (3 columns: best fit, trending, avoid)
8. Recommended Actions (numbered list)

**Features:**
- Dynamic color coding (score-based)
- Status badges (excellent/good/fair/below_market)
- Interactive charts
- Printable report option

---

### Page 3: Salary Intelligence (`/market/salary-intelligence`)

**Purpose:** Advanced salary analysis and negotiation tool  
**Access:** Authenticated users with verified email

**Sections:**
1. Your Salary Position (bar chart with percentiles)
2. Market Comparison (percentile, median, difference)
3. Salary Trends (MoM, YoY, direction with emoji)
4. Salary Movement Chart (6-month line chart)
5. Predictions (6-month and 12-month with confidence)
6. City Comparison Tool (multi-select, comparison chart)
7. Negotiation Calculator (offer input, insights output)

**Features:**
- Interactive city comparison
- Real-time negotiation insights
- Target salary ranges (min/ideal/stretch)
- Talking points generator
- Trend indicators (📈📉➡️)

---

### Page 4: Skill Trends (`/market/skill-trends`)

**Purpose:** Skill intelligence and upskilling roadmap  
**Access:** Authenticated users with verified email

**Sections:**
1. Skill Search & Analysis Tool (input + metrics)
2. 12-Month Skill Evolution Chart (dual-axis line chart)
3. Obsolescence Risk Panel (risk level, score, timeline)
4. Emerging Skills (5 green cards)
5. Hot Skills (5 red cards)
6. Declining Skills (5 orange cards)
7. High-Value Skill Combinations (top 6 pairs)
8. Personalized Upskilling Roadmap (3 phases + current skills)

**Features:**
- Interactive skill analysis
- Trend status emoji (🌱🔥✓📉⚠️)
- Clickable skill cards
- Roadmap generator
- Learning difficulty badges

---

## 🚀 Deployment Steps

### Prerequisites

1. **Environment Requirements:**
   - PHP >= 8.2
   - Laravel 11
   - MySQL >= 8.0
   - Redis >= 6.0
   - Node.js >= 18.x
   - Composer 2.x

2. **External Services:**
   - OpenAI API account with GPT-4 access
   - Email service (SMTP or service like SendGrid)
   - Queue worker infrastructure (Supervisor/Systemd)

3. **Environment Variables:**

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-...

# Redis Configuration (for caching and queues)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@studai.com
MAIL_FROM_NAME="${APP_NAME}"

# Market Intelligence Settings
MARKET_INTELLIGENCE_ENABLED=true
MARKET_DATA_CACHE_TTL=3600
```

---

### Step 1: Database Migration

Run the market intelligence migration:

```bash
php artisan migrate
```

This will create 6 tables:
- market_data_snapshots
- user_market_positions
- salary_trends
- skill_trends
- role_predictions
- competitive_benchmarks

---

### Step 2: Install Frontend Dependencies

Ensure Chart.js is available (already included via CDN in views):

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

No additional npm packages needed (using CDN).

---

### Step 3: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

### Step 4: Configure Queue Worker

For **development** (single process):

```bash
php artisan queue:work --queue=default --tries=3
```

For **production** (using Supervisor):

Create supervisor config `/etc/supervisor/conf.d/market-intelligence-worker.conf`:

```ini
[program:market-intelligence-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start market-intelligence-worker:*
```

---

### Step 5: Configure Scheduler (Cron)

Add to your server's crontab (`crontab -e`):

```cron
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

This will run all scheduled jobs defined in `routes/console.php`:

- **Hourly:** UpdateMarketDataJob (market data refresh)
- **Daily 1 AM:** AnalyzeTrendsJob (salary/skill/role analysis)
- **Daily 3 AM:** UpdateUserPositioningJob (user positioning recalculation)
- **Weekly Sunday 6 AM:** GenerateInsightsJob (AI insights + email digest)

---

### Step 6: Verify Scheduler Configuration

Check scheduled jobs:

```bash
php artisan schedule:list
```

Expected output:

```
  0 * * * *  Market: Update Market Data ................... Next Due: 1 hour from now
  0 1 * * *  Market: Analyze Trends ...................... Next Due: Tomorrow at 1:00 AM
  0 3 * * *  Market: Update User Positioning ............. Next Due: Tomorrow at 3:00 AM
  0 6 * * 0  Market: Generate Weekly Insights ............ Next Due: Sunday at 6:00 AM
```

---

### Step 7: Test Scheduler Manually

Run specific jobs manually for testing:

```bash
# Update market data
php artisan queue:dispatch "App\Jobs\UpdateMarketDataJob"

# Analyze trends
php artisan queue:dispatch "App\Jobs\AnalyzeTrendsJob"

# Update user positioning
php artisan queue:dispatch "App\Jobs\UpdateUserPositioningJob"

# Generate insights
php artisan queue:dispatch "App\Jobs\GenerateInsightsJob"
```

---

### Step 8: Seed Initial Data (Optional)

For testing with realistic data, create a seeder:

```bash
php artisan make:seeder MarketIntelligenceSeeder
```

Example seeder content:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketDataSnapshot;
use App\Models\SalaryTrend;
use App\Models\SkillTrend;
use Carbon\Carbon;

class MarketIntelligenceSeeder extends Seeder
{
    public function run()
    {
        // Create market snapshot
        MarketDataSnapshot::create([
            'data_date' => Carbon::today(),
            'total_jobs' => 45000,
            'avg_salary' => 95000,
            'market_health_score' => 75,
            'demand_supply_ratio' => 1.3,
            'remote_work_percentage' => 42,
            'trending_roles' => json_encode([
                ['role' => 'Software Engineer', 'count' => 5000, 'growth' => 15],
            ]),
            'top_locations' => json_encode([
                ['location' => 'San Francisco', 'count' => 8000, 'avg_salary' => 140000],
            ]),
            'emerging_skills' => json_encode([
                ['skill' => 'React', 'demand_score' => 90],
            ]),
        ]);

        // Run this seeder
        // php artisan db:seed --class=MarketIntelligenceSeeder
    }
}
```

---

### Step 9: Monitor Logs

Watch queue processing:

```bash
tail -f storage/logs/laravel.log
```

Check for errors in background jobs, API calls, AI insights generation.

---

### Step 10: Production Optimization

1. **Enable Opcache** (php.ini):

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

2. **Cache Config and Routes:**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. **Use Laravel Horizon** (optional, for advanced queue monitoring):

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Access Horizon dashboard at `/horizon` (configure auth in `HorizonServiceProvider`).

---

## 🧪 Testing Guide

### Manual Testing Checklist

**API Endpoints:**
- [ ] GET /api/market/overview returns market data
- [ ] GET /api/market/user-position returns readiness score
- [ ] GET /api/market/salary-insights returns salary trends
- [ ] POST /api/market/negotiation-insights returns negotiation data
- [ ] GET /api/market/skill-trends returns skill analysis
- [ ] GET /api/market/skill-combinations returns combinations
- [ ] GET /api/market/upskilling-roadmap returns roadmap
- [ ] GET /api/market/role-predictions returns predictions
- [ ] GET /api/market/competitive-analysis returns benchmarks
- [ ] GET /api/market/recommendations returns recommendations

**Web Pages:**
- [ ] /market/overview loads with charts
- [ ] /market/positioning shows readiness score
- [ ] /market/salary-intelligence displays salary data
- [ ] /market/skill-trends shows skill trends

**Background Jobs:**
- [ ] UpdateMarketDataJob runs successfully
- [ ] AnalyzeTrendsJob analyzes trends
- [ ] UpdateUserPositioningJob updates positions
- [ ] GenerateInsightsJob sends emails

**Scheduler:**
- [ ] Cron executes Laravel scheduler
- [ ] Scheduled jobs appear in schedule:list
- [ ] Jobs dispatch at correct times

---

### Unit Testing

Create tests for services:

```bash
php artisan make:test MarketIntelligenceServiceTest --unit
```

Example test:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AI\MarketIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketIntelligenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_market_overview_returns_data()
    {
        $service = app(MarketIntelligenceService::class);
        $overview = $service->getMarketOverview();

        $this->assertArrayHasKey('market_health_score', $overview);
        $this->assertArrayHasKey('total_jobs', $overview);
        $this->assertIsNumeric($overview['market_health_score']);
    }

    public function test_emerging_skills_returns_array()
    {
        $service = app(MarketIntelligenceService::class);
        $skills = $service->getEmergingSkills();

        $this->assertIsArray($skills);
        $this->assertNotEmpty($skills);
    }
}
```

Run tests:

```bash
php artisan test --filter MarketIntelligenceServiceTest
```

---

### Integration Testing

Test API endpoints:

```bash
php artisan make:test MarketIntelligenceApiTest
```

Example:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_endpoint_requires_auth()
    {
        $response = $this->getJson('/api/market/overview');
        $response->assertStatus(401);
    }

    public function test_overview_endpoint_returns_data_for_authenticated_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/market/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'market_health_score',
                'total_jobs',
                'avg_salary',
                'trending_roles',
                'emerging_skills',
            ]);
    }
}
```

---

## 🐛 Troubleshooting

### Issue: Scheduler Not Running

**Symptoms:** Background jobs not executing automatically

**Solution:**

1. Verify cron is configured:
```bash
crontab -l
```

2. Check Laravel scheduler output manually:
```bash
php artisan schedule:run
```

3. Check cron logs:
```bash
grep CRON /var/log/syslog
```

---

### Issue: Queue Jobs Failing

**Symptoms:** Jobs stuck in queue, errors in logs

**Solution:**

1. Check failed jobs table:
```bash
php artisan queue:failed
```

2. Retry failed jobs:
```bash
php artisan queue:retry all
```

3. Check job timeout settings in `routes/console.php`

4. Increase `max_execution_time` in php.ini if jobs timeout:
```ini
max_execution_time = 300
```

---

### Issue: OpenAI API Errors

**Symptoms:** AI insights missing, "AI service unavailable" errors

**Solution:**

1. Verify API key:
```bash
php artisan tinker
>>> config('openai.api_key')
```

2. Check rate limits (OpenAI dashboard)

3. Review fallback logic in services (should return basic insights on error)

4. Test OpenAI connection:
```bash
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer YOUR_API_KEY"
```

---

### Issue: Cache Not Clearing

**Symptoms:** Stale data, changes not reflecting

**Solution:**

1. Clear all caches:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

2. Restart Redis:
```bash
sudo systemctl restart redis
```

3. Check Redis connection:
```bash
redis-cli ping
```

---

### Issue: Charts Not Displaying

**Symptoms:** Blank chart areas in views

**Solution:**

1. Check browser console for JavaScript errors

2. Verify Chart.js CDN is accessible:
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

3. Ensure API endpoints return valid data (check network tab)

4. Test API endpoint manually:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://your-domain.com/api/market/overview
```

---

### Issue: Email Digest Not Sending

**Symptoms:** Weekly emails not received

**Solution:**

1. Check email configuration:
```bash
php artisan tinker
>>> config('mail')
```

2. Test email manually:
```php
use App\Notifications\MarketInsightsDigestNotification;
use App\Models\User;

$user = User::first();
$user->notify(new MarketInsightsDigestNotification([
    'readiness_score' => 75,
    'percentile_ranking' => 65,
    // ... other data
]));
```

3. Check mail logs:
```bash
tail -f storage/logs/laravel.log | grep "mail"
```

4. Verify GenerateInsightsJob is running:
```bash
php artisan schedule:list
```

---

### Issue: High Memory Usage

**Symptoms:** Jobs crashing, server running out of memory

**Solution:**

1. Check memory_limit in php.ini:
```ini
memory_limit = 512M
```

2. Optimize batch processing in UpdateUserPositioningJob:
```php
// Process users in chunks
User::where('account_type', 'job_seeker')
    ->chunk(100, function ($users) {
        foreach ($users as $user) {
            // Process user
        }
    });
```

3. Monitor memory usage:
```bash
php artisan tinker
>>> memory_get_usage(true) / 1024 / 1024
```

---

### Issue: Slow API Response Times

**Symptoms:** API endpoints taking >3 seconds to respond

**Solution:**

1. Check cache hit rate (should be >80%):
```bash
php artisan tinker
>>> Cache::get('ai_response_...')
```

2. Add database indexes (already in migration):
```sql
SHOW INDEX FROM salary_trends;
```

3. Enable query logging to find slow queries:
```php
\DB::enableQueryLog();
// ... run endpoint
dd(\DB::getQueryLog());
```

4. Consider increasing cache TTL in services (currently 1 hour)

---

## ⚡ Performance Optimization

### Caching Strategy

**Current Implementation:**
- Market overview: 1 hour cache
- User positioning: Calculated daily, cached until next run
- Salary insights: 1 hour cache per role/location
- Skill trends: 1 hour cache per skill

**Optimization Tips:**

1. **Increase cache TTL for stable data:**
```php
// For rarely changing data like role predictions
Cache::remember($cacheKey, 86400, function() {
    // 24 hours instead of 1 hour
});
```

2. **Use cache tags for bulk invalidation:**
```php
Cache::tags(['market', 'overview'])->put($key, $value, 3600);
Cache::tags('market')->flush(); // Clear all market cache
```

3. **Pre-warm cache after data updates:**
```php
// In UpdateMarketDataJob
$this->marketIntelligenceService->getMarketOverview(); // Warms cache
```

---

### Database Optimization

**Current Indexes (already in migration):**
- Composite indexes on frequently queried columns
- Foreign keys with cascade delete
- Full-text indexes on searchable columns

**Additional Optimizations:**

1. **Analyze slow queries:**
```sql
SHOW FULL PROCESSLIST;
EXPLAIN SELECT * FROM salary_trends WHERE role = 'Engineer';
```

2. **Add covering indexes if needed:**
```php
// In new migration
$table->index(['role', 'location', 'data_date', 'median_salary']);
```

3. **Partition large tables by date:**
```sql
-- For salary_trends table (if >1M rows)
ALTER TABLE salary_trends PARTITION BY RANGE (YEAR(data_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

---

### Queue Optimization

**Current Configuration:**
- 4 queue workers (Supervisor config)
- 3 retries per job
- 1 hour max job time

**Optimization Tips:**

1. **Prioritize jobs with separate queues:**
```php
// In Job class
public $queue = 'high-priority';

// In supervisor config
--queue=high-priority,default
```

2. **Batch processing for user updates:**
```php
// Already implemented in UpdateUserPositioningJob
// Processes in chunks, logs every 100 users
```

3. **Monitor job performance with Horizon:**
```bash
composer require laravel/horizon
php artisan horizon:install
```

---

## 📈 Monitoring & Metrics

### Key Metrics to Track

1. **System Health:**
   - API response times (<500ms target)
   - Queue job success rate (>95% target)
   - Cache hit rate (>80% target)
   - Database query time (<100ms target)

2. **Business Metrics:**
   - Active users using market intelligence features
   - Average readiness score across users
   - Email open rate for weekly digest
   - API endpoint usage patterns

3. **Data Quality:**
   - Market data snapshot freshness (<2 hours)
   - User positioning calculation frequency (daily)
   - AI insights generation success rate (>90%)
   - Trend analysis completion time (<30 min)

---

### Monitoring Tools

**Laravel Telescope** (development):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at `/telescope` to see:
- Requests
- Queries
- Jobs
- Mail
- Cache operations

**Laravel Horizon** (production):
```bash
composer require laravel/horizon
php artisan horizon:install
```

Access at `/horizon` to see:
- Queue metrics
- Job throughput
- Failed jobs
- Wait times

**Application Performance Monitoring (APM):**

Consider integrating:
- **New Relic:** Full APM suite
- **Sentry:** Error tracking
- **DataDog:** Infrastructure monitoring

---

## 📝 Maintenance

### Daily Tasks

- [ ] Check queue worker status
- [ ] Review failed jobs
- [ ] Monitor API error rates
- [ ] Check email delivery success

### Weekly Tasks

- [ ] Review scheduler execution logs
- [ ] Analyze slow query logs
- [ ] Check disk space for logs
- [ ] Review cache hit rates

### Monthly Tasks

- [ ] Analyze user engagement with market intelligence features
- [ ] Review and optimize slow API endpoints
- [ ] Clean up old market data snapshots (>6 months)
- [ ] Update AI prompt templates if needed
- [ ] Review OpenAI API usage and costs

---

## 🔐 Security Considerations

1. **API Authentication:**
   - All endpoints require Sanctum authentication
   - Rate limiting configured in `app/Http/Kernel.php`
   - CORS settings reviewed

2. **Data Privacy:**
   - User salary data encrypted at rest (if using database encryption)
   - Email digests contain aggregated data only
   - No PII exposed in logs

3. **External API Security:**
   - OpenAI API key stored in `.env`, never committed
   - API key rotation schedule (quarterly recommended)
   - Monitor for unusual API usage

4. **Queue Job Security:**
   - Jobs run with user context isolation
   - Retry limits prevent infinite loops
   - Job payloads don't contain sensitive data

---

## 📞 Support

For technical issues:
1. Check logs: `storage/logs/laravel.log`
2. Review this documentation
3. Check Laravel 11 official docs: https://laravel.com/docs/11.x
4. OpenAI API docs: https://platform.openai.com/docs

---

**System Version:** 1.0.0  
**Last Updated:** January 2025  
**Total Code:** ~7,780 lines across 20+ files  
**Database Tables:** 6 tables with optimized indexes  
**API Endpoints:** 10 authenticated endpoints  
**Web Pages:** 4 responsive Blade views  
**Background Jobs:** 4 scheduled jobs  
**Caching:** Redis with 1-hour TTL  
**AI Integration:** OpenAI GPT-4 with fallbacks
