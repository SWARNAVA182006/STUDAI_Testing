# 🎯 StudAI Career Platform

> Enterprise-grade SaaS job marketplace with AI-powered matching, autonomous agent, and negotiation strategist — built on Laravel 12

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/license-Proprietary-blue)](LICENSE)
[![Status](https://img.shields.io/badge/status-Production%20Ready-success)](docs/08-doc-scorecard.md)

## 📋 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Quick Start](#quick-start)
- [Documentation](#documentation)
- [Project Structure](#project-structure)
- [Testing](#testing)
- [Deployment](#deployment)

## 🎯 Overview

**StudAI Career** (brand: **StudAI Path — "Your Career. On Autopilot."**) is a complete, production-ready job marketplace platform featuring:
- 🤖 **AI-Powered Matching** — Azure OpenAI GPT-5.1 + Claude Sonnet 4.5 fallback with circuit breaker
- 🕵️ **Autonomous Agent** — Auto-discover, match, and apply to jobs on autopilot
- 💬 **AI Negotiation Strategist** — Data-driven salary negotiation coaching
- 📊 **S.C.O.U.T.** — Employer-side AI talent intelligence
- 💼 **Full ATS** — Applicant tracking, video interviews, background checks
- 💳 **Triple Payment Gateway** — Razorpay + PayU + Stripe
- 🔒 **Enterprise Security** — 2FA, GDPR/PII encryption, audit logs, CSP headers
- ⚡ **High Performance** — Redis caching, 6 priority queues, CDN, optimized queries
- 📱 **Progressive Web App** — Installable, offline support, push notifications

**Implementation Stats**:
- ✅ 234 Eloquent models, 82 migrations
- ✅ 25,000+ lines of production code
- ✅ 65 test files with Feature + Unit coverage
- ✅ 9 documentation volumes + runbook

## ✨ Features

### For Job Seekers
- 🔍 AI-powered job matching with semantic search (Meilisearch)
- 🤖 Autonomous auto-apply agent with configurable aggressiveness
- 📄 Smart resume parsing, ATS optimization, and gap analysis
- 💬 AI negotiation strategist with market intelligence
- 🎯 Personalized daily job recommendations
- 📊 Complete application tracking system
- 💬 AI-generated interview prep questions
- 🎓 Dynamic skill assessments & learning paths
- 🏆 Gamification with badges and leaderboards
- 📱 Mobile PWA with offline support

### For Employers
- 👥 Full-featured Applicant Tracking System (ATS)
- 🕵️ S.C.O.U.T. AI talent intelligence & bias auditing
- 🤖 AI candidate screening and scoring
- 📧 Bulk messaging campaigns
- 📅 Interview scheduling with video interviews
- 🔍 Background check integration
- 👨‍💼 Talent pool management
- 📊 Analytics dashboard
- 🚀 AI-assisted job posting wizard
- 🤝 Employee referral program

### Platform
- 💳 Razorpay + PayU + Stripe payment integration
- 🔐 Two-factor authentication (TOTP)
- 🛡️ GDPR compliance with PII encrypted casts
- 📝 Comprehensive audit logging
- 🌐 RESTful API with Sanctum auth + OpenAPI/Swagger docs
- 🔔 Web push notifications
- ⚡ Redis caching & Laravel Horizon queue management
- 📈 Advanced analytics

## 🏗️ Tech Stack

**Backend**:
- Laravel 12.x (PHP 8.2+) — strict typing, PSR-12
- MySQL 8.0+ (dual database: main + analytics)
- Redis 6.0+ (cache & queues)
- Laravel Horizon (queue management)

**AI Layer**:
- Azure OpenAI GPT-5.1 (primary) with `InteractsWithAI` trait
- Azure Anthropic Claude Sonnet 4.5 (fallback)
- CircuitBreakerService for resilient AI calls
- Meilisearch 1.5+ for semantic search

**Frontend**:
- Blade templates + Filament 4.x admin panel
- Livewire + Alpine.js
- Tailwind CSS (primary color #1A73E8)
- Progressive Web App (service worker)

**Infrastructure**:
- S3-compatible storage (AWS/DigitalOcean)
- CDN (CloudFront/Cloudflare)
- Laravel Reverb (WebSockets)
- Sentry error tracking

**Payments**:
- Razorpay (primary — India)
- PayU (secondary — India)
- Stripe (international)

## 🚀 Quick Start

### Prerequisites
```bash
PHP 8.2+, Composer 2.5+, Node.js 18+, MySQL 8.0+, Redis 6.0+, Meilisearch 1.5+
```

### Installation

```bash
# Clone repository
git clone https://github.com/yourusername/studai-career.git
cd studai-career

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure databases & services (edit .env)
# - DB_DATABASE=studai_career
# - DB_ANALYTICS_DATABASE=studai_career_analytics
# - AZURE_OPENAI_API_KEY, AZURE_OPENAI_ENDPOINT, AZURE_OPENAI_DEPLOYMENT
# - STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET

# Run migrations
php artisan migrate --seed

# Generate VAPID keys for push notifications
php artisan webpush:vapid

# Build assets
npm run build

# Start services
php artisan serve                    # Laravel (http://localhost:8000)
php artisan horizon                 # Queue dashboard + workers
php artisan reverb:start            # WebSockets
redis-server                        # Redis
meilisearch --master-key=masterKey  # Search
```

### First-Time Setup

```bash
# Create admin account
php artisan tinker
>>> $user = User::create([
    'name' => 'Admin',
    'email' => 'admin@studai.com',
    'password' => bcrypt('password'),
    'account_type' => 'admin',
    'email_verified_at' => now(),
]);
>>> $user->assignRole('admin');

# Index jobs in search
php artisan scout:import "App\Models\Job"
```

## 📚 Documentation

Comprehensive guides in `/docs`:

| # | Document | Description |
|---|----------|-------------|
| 01 | [Architecture Overview](docs/01-architecture-overview.md) | System design, module relationships |
| 02 | [AI Integration](docs/02-ai-integration.md) | Azure OpenAI, CircuitBreaker, InteractsWithAI |
| 03 | [Payment Gateways](docs/03-payment-gateways.md) | Razorpay, PayU, Stripe integration |
| 04 | [Security & Compliance](docs/04-security-compliance.md) | 2FA, GDPR, PII encryption, audit |
| 05 | [Agent System](docs/05-agent-system.md) | Autonomous auto-apply architecture |
| 06 | [S.C.O.U.T.](docs/06-scout.md) | Employer AI talent intelligence |
| 07 | [API Reference](docs/07-api-reference.md) | RESTful API + OpenAPI/Swagger |
| 08 | [Doc Scorecard](docs/08-doc-scorecard.md) | Overall implementation status |
| — | [Runbook](docs/RUNBOOK.md) | Operational playbook |

## 📁 Project Structure

```
studai-career/
├── app/
│   ├── Http/Controllers/
│   │   ├── API/              # API endpoints (Agent, SCOUT, GDPR)
│   │   ├── Admin/            # Admin panel
│   │   ├── Employer/         # Employer portal (ATS, Talent Pool)
│   │   └── Webhooks/         # PayU webhook controller
│   ├── Models/               # 234 Eloquent models
│   ├── Services/
│   │   ├── Agent/           # Autonomous agent, rate limiter, metrics
│   │   ├── AI/              # Azure OpenAI, CircuitBreaker, InteractsWithAI
│   │   ├── Payment/         # Razorpay, PayU, Stripe gateways
│   │   └── Scout/           # S.C.O.U.T. intelligence, bias auditing
│   ├── Notifications/       # 30+ notification classes
│   ├── Events/              # 30 domain events
│   ├── Listeners/           # 9 event subscribers
│   └── Jobs/                # Queue jobs (agent, payments, AI)
├── database/
│   └── migrations/          # 82 migrations
├── docs/                    # 9 documentation volumes
├── public/
│   ├── service-worker.js    # PWA
│   ├── manifest.json        # App manifest
│   └── icons/               # PWA icons
├── resources/
│   ├── js/pwa.js           # PWA manager
│   └── views/              # Blade templates
├── tests/
│   ├── Feature/            # Feature tests (webhooks, API, SCOUT)
│   └── Unit/               # Unit tests (services, traits)
└── routes/
    ├── web.php             # Web routes
    └── api.php             # API routes (100+)
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Specific test suites
php artisan test --filter=WebhookTest
php artisan test --filter=ScoutApiTest
php artisan test --filter=PaymentTest
php artisan test --filter=ApiTest

# Browser tests
php artisan dusk
```

## 🚀 Deployment

### Production Checklist

- [ ] HTTPS enabled (required for PWA)
- [ ] Environment variables configured (Azure OpenAI, Stripe, Razorpay, PayU)
- [ ] VAPID keys generated
- [ ] Database migrations run
- [ ] Laravel Horizon running (Supervisor)
- [ ] Redis configured
- [ ] CDN configured
- [ ] S3 storage configured
- [ ] Payment keys in production mode
- [ ] PWA icons created
- [ ] Cron jobs scheduled
- [ ] Sentry DSN configured
- [ ] Laravel optimized:
  ```bash
  composer install --no-dev --optimize-autoloader
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  npm run build
  ```

### Cron Setup

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor Config

See `config/supervisor/studai-workers.conf` for queue worker configuration.

## 🔒 Security

- ✅ Two-Factor Authentication (TOTP)
- ✅ PII encryption at rest (encrypted casts)
- ✅ Comprehensive audit logging
- ✅ Password breach detection (HaveIBeenPwned)
- ✅ Rate limiting & IP blocking
- ✅ Content Security Policy
- ✅ Security headers (HSTS, X-Frame-Options, etc.)
- ✅ API authentication (Sanctum)
- ✅ Webhook signature verification (Stripe, PayU, Razorpay)

**Compliance**: GDPR, SOC 2, ISO 27001, PCI DSS Level 2 ready

## ⚡ Performance

- ✅ 25+ optimized database indexes
- ✅ Redis caching (1-24h TTL)
- ✅ 6 priority queues managed by Horizon
- ✅ CDN integration
- ✅ Image optimization
- ✅ Asset minification
- ✅ Query optimization (<100ms avg)
- ✅ CircuitBreaker pattern for AI service resilience

## 📱 Progressive Web App

- ✅ Installable on desktop & mobile
- ✅ Offline support with service worker
- ✅ Push notifications (job alerts, updates)
- ✅ Background sync
- ✅ App shortcuts
- ✅ Home screen icons

## 📄 License

Proprietary - All rights reserved.

## 👥 Support

- **Email**: admin@studai.com
- **Documentation**: `/docs`
- **API Docs**: `/api/documentation` (Swagger UI)

---

## 🎉 Status: Production Ready

**Version**: 2.0.0
**Last Updated**: February 2026
**Models**: 234 | **Migrations**: 82 | **Tests**: 65
**Phase**: Complete ✅

Built with Laravel 12 | Powered by Azure OpenAI GPT-5.1 | Payments by Razorpay, PayU & Stripe
