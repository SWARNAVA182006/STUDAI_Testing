# Contributing to StudAI Career Platform

Thank you for considering contributing to the StudAI Career Platform! This document provides guidelines for contributing to the project.

## 🎯 Project Vision

StudAI Career Platform aims to be the most comprehensive, AI-powered job marketplace platform with:
- Intelligent job matching using semantic search
- Complete applicant tracking system for employers
- Progressive Web App for mobile-first experience
- Enterprise-grade security and performance

## 📋 Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [Development Workflow](#development-workflow)
4. [Coding Standards](#coding-standards)
5. [Testing Guidelines](#testing-guidelines)
6. [Pull Request Process](#pull-request-process)
7. [Areas for Contribution](#areas-for-contribution)

## 🤝 Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inspiring community for all. We expect all participants to:
- Use welcoming and inclusive language
- Be respectful of differing viewpoints
- Accept constructive criticism gracefully
- Focus on what is best for the community
- Show empathy towards other community members

### Unacceptable Behavior

- Harassment, trolling, or discriminatory comments
- Publishing others' private information
- Spamming or self-promotion
- Any conduct that could be considered inappropriate in a professional setting

## 🚀 Getting Started

### Prerequisites

Ensure you have:
- PHP 8.2 or higher
- Composer 2.5+
- Node.js 18+ and npm
- MySQL 8.0+
- Redis 6.0+
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:
```bash
git clone https://github.com/YOUR-USERNAME/studai-career.git
cd studai-career
```

3. Add upstream remote:
```bash
git remote add upstream https://github.com/ORIGINAL-OWNER/studai-career.git
```

### Setup Development Environment

Run the quick start script:
```bash
# Linux/Mac
./scripts/quick-start.sh

# Windows
scripts\quick-start.bat
```

Or manually:
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

## 💻 Development Workflow

### Branch Naming Convention

Create feature branches with descriptive names:
- `feature/job-search-filters` - New features
- `bugfix/payment-gateway-error` - Bug fixes
- `enhancement/ai-matching-algorithm` - Improvements
- `docs/api-documentation` - Documentation updates

```bash
git checkout -b feature/your-feature-name
```

### Commit Message Format

Follow conventional commits:
```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, no logic change)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples**:
```
feat(jobs): add advanced salary range filter

Implement salary range filtering with min/max inputs
and currency selection for job search.

Closes #123
```

```
fix(payment): resolve Razorpay webhook signature verification

The webhook signature was failing due to incorrect
hash calculation. Updated to match Razorpay specs.

Fixes #456
```

### Keep Your Fork Updated

```bash
git fetch upstream
git checkout main
git merge upstream/main
git push origin main
```

## 📝 Coding Standards

### PHP Style Guide

Follow PSR-12 coding standards:

```php
<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\Cache;

class JobMatchingService
{
    public function matchJobs(User $user, int $limit = 10): Collection
    {
        $cacheKey = "user_matches_{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user, $limit) {
            // Implementation
        });
    }
}
```

**Key Points**:
- Use type hints for parameters and return types
- Use camelCase for method names
- Use UPPER_CASE for constants
- Document complex logic with comments
- Keep methods focused (single responsibility)

### JavaScript Style Guide

Follow Airbnb JavaScript style guide:

```javascript
// Good
const jobCards = document.querySelectorAll('.job-card');
jobCards.forEach((card) => {
    card.addEventListener('click', handleJobClick);
});

// Bad
var cards = document.querySelectorAll('.job-card')
for (let i = 0; i < cards.length; i++) {
    cards[i].onclick = handleJobClick
}
```

### Database Conventions

**Migrations**:
```php
Schema::create('job_applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('job_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('status', ['pending', 'shortlisted', 'interview', 'rejected', 'accepted']);
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['job_id', 'user_id']);
    $table->index(['user_id', 'created_at']);
});
```

**Models**:
```php
class JobApplication extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'job_id',
        'user_id',
        'status',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
```

### Blade Templates

```blade
{{-- Good: Clean, semantic structure --}}
<div class="job-card bg-white rounded-lg shadow-md p-6">
    <h3 class="text-xl font-semibold mb-2">
        {{ $job->title }}
    </h3>
    
    <p class="text-gray-600 mb-4">
        {{ $job->company->name }}
    </p>
    
    @if($job->salary_min && $job->salary_max)
        <div class="salary-range">
            ₹{{ number_format($job->salary_min) }} - ₹{{ number_format($job->salary_max) }}
        </div>
    @endif
</div>
```

## 🧪 Testing Guidelines

### Write Tests for New Features

Every new feature should include tests:

```php
// tests/Feature/JobApplicationTest.php
class JobApplicationTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function user_can_apply_to_job()
    {
        $user = User::factory()->create(['account_type' => 'job_seeker']);
        $job = Job::factory()->create();
        
        $this->actingAs($user)
            ->post(route('jobs.apply', $job))
            ->assertRedirect()
            ->assertSessionHas('success');
            
        $this->assertDatabaseHas('job_applications', [
            'user_id' => $user->id,
            'job_id' => $job->id,
            'status' => 'pending',
        ]);
    }
    
    /** @test */
    public function user_cannot_apply_twice_to_same_job()
    {
        $user = User::factory()->create(['account_type' => 'job_seeker']);
        $job = Job::factory()->create();
        
        JobApplication::create([
            'user_id' => $user->id,
            'job_id' => $job->id,
            'status' => 'pending',
        ]);
        
        $this->actingAs($user)
            ->post(route('jobs.apply', $job))
            ->assertStatus(422);
    }
}
```

### Run Tests Before Submitting

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/JobApplicationTest.php

# Run with coverage
php artisan test --coverage

# Run browser tests
php artisan dusk
```

### Test Coverage Requirements

- Aim for >80% code coverage
- All API endpoints must have tests
- All payment flows must have tests
- All AI features must have tests
- Critical user journeys must have browser tests

## 🔄 Pull Request Process

### Before Submitting

1. **Update your branch**:
```bash
git fetch upstream
git rebase upstream/main
```

2. **Run tests**:
```bash
php artisan test
npm run test
```

3. **Check code style**:
```bash
./vendor/bin/pint
npm run lint
```

4. **Update documentation** if needed

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issues
Closes #123

## How Has This Been Tested?
- [ ] Unit tests
- [ ] Feature tests
- [ ] Browser tests
- [ ] Manual testing

## Screenshots (if applicable)
[Add screenshots]

## Checklist
- [ ] My code follows the project's style guidelines
- [ ] I have performed a self-review
- [ ] I have commented my code where necessary
- [ ] I have updated the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix/feature works
- [ ] New and existing tests pass locally
```

### Review Process

1. Maintainer will review within 48 hours
2. Address review comments
3. Request re-review when ready
4. Once approved, maintainer will merge

## 🎯 Areas for Contribution

### High Priority

1. **Testing**
   - Increase test coverage
   - Add browser tests for critical flows
   - Performance testing

2. **Documentation**
   - API usage examples
   - Video tutorials
   - Troubleshooting guides

3. **Performance**
   - Database query optimization
   - Cache strategy improvements
   - Frontend bundle size reduction

### Feature Enhancements

1. **Job Matching Algorithm**
   - Improve AI matching accuracy
   - Add more matching factors
   - A/B testing framework

2. **PWA Features**
   - Offline job applications
   - Background sync improvements
   - Enhanced push notifications

3. **Employer Tools**
   - Advanced analytics
   - Bulk import candidates
   - Interview scheduling integrations

4. **Integrations**
   - LinkedIn profile import
   - Calendar integrations (Google, Outlook)
   - Video interview platforms (Zoom, Teams)

### Bug Fixes

Check [GitHub Issues](https://github.com/OWNER/studai-career/issues) labeled `good first issue` or `help wanted`.

## 📚 Resources

### Laravel Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel News](https://laravel-news.com)
- [Laracasts](https://laracasts.com)

### Project Documentation
- [Installation Guide](docs/INSTALLATION_CHECKLIST.md)
- [API Documentation](docs/API_DOCUMENTATION.md)
- [PWA Guide](docs/PWA_IMPLEMENTATION.md)

### Getting Help

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For questions and ideas
- **Email**: dev@studai.com

## 🏆 Recognition

Contributors will be recognized in:
- `CONTRIBUTORS.md` file
- Project README
- Release notes
- Annual contributor highlights

## 📄 License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License).

---

Thank you for contributing to StudAI Career Platform! 🚀

*Happy coding!*
