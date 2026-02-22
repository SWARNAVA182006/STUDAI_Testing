# Azure OpenAI Integration Guide - StudAI Career Platform

## 🎯 Overview

StudAI Career platform uses **Azure OpenAI Service** as the primary AI provider for all AI-powered features. This document outlines the configuration, implementation patterns, and usage guidelines.

## 🔑 Why Azure OpenAI?

- **Enterprise-grade reliability**: 99.9% SLA
- **Data residency**: Compliance with Indian data regulations
- **Cost optimization**: Better pricing for high-volume usage
- **Security**: Enterprise security and privacy guarantees
- **Fallback ready**: Seamless fallback to OpenAI API if needed

## 📦 Current Configuration

### Model: GPT-4o (GPT-4 Omni)
- **Deployment ID**: `gpt-4o`
- **API Version**: `2024-08-01-preview`
- **Context Window**: 128,000 tokens
- **Max Output**: 4,096 tokens (configurable)

### Environment Variables (.env)
```env
# Azure OpenAI Configuration
AZURE_OPENAI_API_KEY=your_azure_api_key_here
AZURE_OPENAI_ENDPOINT=https://your-resource.openai.azure.com/
AZURE_OPENAI_DEPLOYMENT_ID=gpt-4o
AZURE_OPENAI_API_VERSION=2024-08-01-preview

# AI Service Configuration
AI_PRIMARY_PROVIDER=azure
AI_MODEL_CHAT=gpt-4o
AI_MODEL_EMBEDDINGS=text-embedding-3-large
AI_MAX_TOKENS=4096
AI_TEMPERATURE=0.7
AI_CACHE_ENABLED=true
AI_CACHE_TTL=3600

# OpenAI API (Fallback)
OPENAI_API_KEY=your_openai_key_here
OPENAI_ORGANIZATION=your_org_id
```

## 🏗️ Architecture

### Service Layer Structure
```
app/Services/AI/
├── AIService.php              # Base service with Azure OpenAI integration
├── ResumeAnalyzerService.php  # Resume parsing and skills extraction
├── JobMatchingService.php     # Semantic job matching with embeddings
├── CoverLetterService.php     # AI-generated cover letters
├── InterviewPrepService.php   # Interview question generation
├── CareerAdvisorService.php   # Career path recommendations
└── SkillsExtractorService.php # Extract skills from job descriptions
```

### Caching Strategy
```php
// All AI responses are cached to reduce costs and improve performance
// Cache keys: 'ai_cache:{feature}:{hash(input)}'
// TTLs: See config/ai.php 'cache.ttl' section

// Example:
$cacheKey = 'ai_cache:resume_analysis:' . md5(json_encode($resumeData));
return Cache::remember($cacheKey, 3600, function() use ($resumeData) {
    return $this->callAzureOpenAI($resumeData);
});
```

## 🚀 AI Features Using Azure OpenAI

### 1. Resume Analyzer (`ResumeAnalyzerService`)
**Purpose**: Parse resumes, extract skills, experience, education  
**Model**: GPT-4o  
**Cache TTL**: 1 hour  
**Input**: PDF/DOCX resume file  
**Output**: Structured JSON with skills, experience, education, summary

**Prompt Pattern**:
```
Analyze the following resume and extract:
1. Skills (technical and soft skills)
2. Work experience (company, role, duration, achievements)
3. Education (degree, institution, year)
4. Professional summary (2-3 sentences)

Return as JSON only, no markdown.
```

### 2. Job Matching Engine (`JobMatchingService`)
**Purpose**: Semantic matching between user profiles and job listings  
**Model**: GPT-4o + text-embedding-3-large  
**Cache TTL**: 2 hours  
**Input**: User profile + job listing  
**Output**: Match score (0-100), reasoning, skill gaps

**Algorithm**:
```
1. Generate embeddings for user profile (cached 24h)
2. Generate embeddings for job description (cached until job updated)
3. Calculate cosine similarity
4. GPT-4o analyzes detailed match with weighted scoring:
   - Skills match: 40%
   - Experience level: 20%
   - Location preference: 15%
   - Salary expectation: 15%
   - Culture fit: 10%
```

### 3. Cover Letter Generator (`CoverLetterService`)
**Purpose**: AI-generated personalized cover letters  
**Model**: GPT-4o  
**Cache TTL**: 30 minutes  
**Input**: User profile + job listing + tone preference  
**Output**: 3 variants (concise, standard, detailed)

**Tones Available**: Professional, Enthusiastic, Creative

### 4. Interview Prep (`InterviewPrepService`)
**Purpose**: Generate role-specific interview questions  
**Model**: GPT-4o  
**Cache TTL**: 1 hour  
**Input**: Job title, seniority level, skills required  
**Output**: 10 questions with ideal answers and evaluation criteria

**Difficulty Levels**: Entry, Mid, Senior, Expert

### 5. Career Advisor (`CareerAdvisorService`)
**Purpose**: Career path recommendations and skill gap analysis  
**Model**: GPT-4o  
**Cache TTL**: 24 hours  
**Input**: Current profile, aspirations, industry trends  
**Output**: 5 career paths with learning roadmaps

### 6. Skills Extractor (`SkillsExtractorService`)
**Purpose**: Extract required skills from job descriptions  
**Model**: GPT-4o  
**Cache TTL**: 24 hours (job descriptions change rarely)  
**Input**: Job description text  
**Output**: Technical skills, soft skills, required experience

## 💰 Cost Optimization

### Token Usage Tracking
All AI calls are logged in `ai_usage_logs` table:
```php
Schema::create('ai_usage_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('feature'); // 'resume_analysis', 'job_matching', etc.
    $table->string('model'); // 'gpt-4o'
    $table->integer('input_tokens');
    $table->integer('output_tokens');
    $table->decimal('cost_usd', 10, 6);
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

### Pricing (GPT-4o)
- **Input**: $5 per 1M tokens
- **Output**: $15 per 1M tokens

### Monthly Credit Limits by Tier
```php
'free' => 10 credits/month (~10 AI requests)
'professional' => 200 credits/month
'premium' => 1000 credits/month
'enterprise' => unlimited
```

## 🔒 Rate Limiting

Implemented at middleware level:
```php
// config/ai.php
'rate_limits' => [
    'free' => ['max_requests_per_hour' => 5],
    'professional' => ['max_requests_per_hour' => 50],
    'premium' => ['max_requests_per_hour' => 200],
    'enterprise' => ['max_requests_per_hour' => -1], // unlimited
]
```

## 🛡️ Error Handling & Fallback

### Retry Strategy
```php
// Exponential backoff: 1s, 2s, 4s
'retry' => [
    'max_attempts' => 3,
    'delay' => 1000, // ms
    'backoff' => 'exponential',
]
```

### Fallback Chain
1. **Try Azure OpenAI** (primary)
2. **If fails**: Check cache for recent response
3. **If no cache**: Try OpenAI API (fallback)
4. **If both fail**: Return basic non-AI response or error

### Example Implementation
```php
try {
    $response = $this->callAzureOpenAI($prompt);
} catch (AzureOpenAIException $e) {
    Log::warning('Azure OpenAI failed, trying fallback', ['error' => $e->getMessage()]);
    
    // Try cached response
    if ($cached = Cache::get($cacheKey)) {
        return $cached;
    }
    
    // Try OpenAI API
    if (config('ai.fallback.use_openai_if_azure_fails')) {
        $response = $this->callOpenAI($prompt);
    } else {
        throw $e;
    }
}
```

## 📊 Monitoring & Analytics

### Metrics to Track
1. **API Response Times**: p50, p95, p99 latencies
2. **Token Usage**: Input/output tokens per feature
3. **Cost per User**: Monthly AI spend by subscription tier
4. **Cache Hit Rate**: Percentage of requests served from cache
5. **Error Rate**: Failed API calls / total calls
6. **Feature Usage**: Which AI features are most used

### Dashboards
- Laravel Telescope: `/telescope` (local only)
- Custom Analytics: Separate `studai_career_analytics` database

## 🧪 Testing AI Services

### Local Development
```bash
# Use test API key with rate limits
AZURE_OPENAI_API_KEY=test_key_12345

# Reduce costs with lower token limits
AI_MAX_TOKENS=512
AI_TEMPERATURE=0.5

# Enable aggressive caching
AI_CACHE_ENABLED=true
AI_CACHE_TTL=86400 # 24 hours
```

### Test Cases
```php
// tests/Feature/AI/ResumeAnalyzerTest.php
public function test_resume_analysis_returns_structured_data()
{
    $resume = UploadedFile::fake()->create('resume.pdf', 100);
    
    $response = $this->post('/api/ai/analyze-resume', [
        'resume' => $resume,
    ]);
    
    $response->assertOk()
        ->assertJsonStructure([
            'skills' => ['technical', 'soft'],
            'experience' => [['company', 'role', 'duration']],
            'education' => [['degree', 'institution', 'year']],
            'summary',
        ]);
}
```

## 🔄 Migration from Legacy (api_proxy.php)

### Old Architecture (default.php + api_proxy.php)
```javascript
// Client-side state management
// Direct Azure OpenAI calls from browser via proxy
fetch('/api_proxy.php', {
    method: 'POST',
    body: JSON.stringify({ messages, apiKey })
});
```

### New Architecture (Laravel Services)
```php
// Server-side AI services with caching and tracking
app(ResumeAnalyzerService::class)->analyze($resume);
```

### Migration Checklist
- [x] Environment variables configured
- [x] config/ai.php created
- [ ] AIService base class implemented
- [ ] Feature-specific services created
- [ ] Migrations for ai_usage_logs table
- [ ] Middleware for rate limiting
- [ ] Unit tests for all AI services
- [ ] Integration tests with real API
- [ ] Cost monitoring dashboard

## 🎓 Best Practices

1. **Always cache AI responses** - Use appropriate TTLs per feature
2. **Track token usage** - Log every API call for cost monitoring
3. **Implement rate limits** - Prevent abuse and control costs
4. **Use system prompts** - Define consistent AI behavior across features
5. **Validate outputs** - Parse and validate JSON responses
6. **Graceful degradation** - Always have a fallback (cache, OpenAI API, or basic response)
7. **Monitor latency** - Set timeouts and track p95 response times
8. **Test with production data** - Use anonymized real resumes/jobs for testing

## 📚 Next Steps

1. **Phase 2**: Create database migrations for AI-related tables
2. **Phase 4**: Implement AIService base class and feature services
3. **Testing**: Write comprehensive test suite for AI features
4. **Documentation**: Add inline code documentation with examples
5. **Monitoring**: Set up alerts for high costs or error rates

---

**Last Updated**: October 2025  
**Configuration File**: `config/ai.php`  
**Documentation**: This file + inline code comments
