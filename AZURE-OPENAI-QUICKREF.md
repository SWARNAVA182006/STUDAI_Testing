# Azure OpenAI Quick Reference - StudAI Career

## ✅ Configuration Status

**Primary AI Provider**: Azure OpenAI Service  
**Model**: GPT-4o (GPT-4 Omni)  
**Fallback**: OpenAI API  

---

## 🔑 Environment Variables (.env)

```env
# Azure OpenAI (Primary)
AZURE_OPENAI_API_KEY=your_azure_api_key_here
AZURE_OPENAI_ENDPOINT=https://your-resource-name.openai.azure.com
AZURE_OPENAI_DEPLOYMENT_ID=gpt-4o
AZURE_OPENAI_API_VERSION=2024-08-01-preview

# AI Configuration
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

---

## 📦 Configuration File: `config/ai.php`

All AI service settings are centralized in `config/ai.php`:

- **Provider selection** (`azure` or `openai`)
- **Model parameters** (max_tokens, temperature, etc.)
- **Caching strategy** (TTLs per feature)
- **Rate limits** (by subscription tier)
- **Cost tracking** (token usage and pricing)
- **Retry logic** (exponential backoff)
- **Fallback behavior** (Azure → Cache → OpenAI → Basic response)

---

## 🚀 AI Features Using Azure OpenAI GPT-4o

| Feature | Service Class | Cache TTL | Cost Credits |
|---------|--------------|-----------|--------------|
| **Resume Analyzer** | `ResumeAnalyzerService` | 1 hour | 2 credits |
| **Job Matching** | `JobMatchingService` | 2 hours | 1 credit |
| **Cover Letter Generator** | `CoverLetterService` | 30 min | 3 credits |
| **Interview Prep** | `InterviewPrepService` | 1 hour | 2 credits |
| **Career Advisor** | `CareerAdvisorService` | 24 hours | 5 credits |
| **Skills Extractor** | `SkillsExtractorService` | 24 hours | 1 credit |

---

## 💰 Subscription Tiers & AI Credits

| Tier | Monthly AI Credits | Requests/Hour |
|------|-------------------|---------------|
| **Free** | 10 credits (~10 requests) | 5 |
| **Professional** | 200 credits | 50 |
| **Premium** | 1,000 credits | 200 |
| **Enterprise** | Unlimited | Unlimited |

---

## 🔧 Usage Example (Service Layer)

```php
use App\Services\AI\ResumeAnalyzerService;

// Inject service via dependency injection
public function analyzeResume(Request $request, ResumeAnalyzerService $analyzer)
{
    // Check user has enough AI credits
    if (!auth()->user()->hasAICredits(2)) {
        return response()->json(['error' => 'Insufficient AI credits'], 403);
    }
    
    // Analyze resume (uses Azure OpenAI GPT-4o with caching)
    $result = $analyzer->analyze($request->file('resume'));
    
    // Deduct credits
    auth()->user()->deductAICredits(2);
    
    return response()->json($result);
}
```

---

## 📊 Token Usage Tracking

All AI calls are logged to `ai_usage_logs` table:

```sql
SELECT 
    feature,
    COUNT(*) as requests,
    SUM(input_tokens) as total_input_tokens,
    SUM(output_tokens) as total_output_tokens,
    SUM(cost_usd) as total_cost
FROM ai_usage_logs
WHERE user_id = ?
  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY feature;
```

---

## 🛡️ Error Handling Flow

```
1. Try Azure OpenAI (gpt-4o deployment)
   ↓ (if fails)
2. Check Redis cache for recent response
   ↓ (if not found)
3. Try OpenAI API (fallback provider)
   ↓ (if both fail)
4. Return basic non-AI response OR error
```

---

## 🧪 Testing Commands

```bash
# Test Azure OpenAI configuration
php artisan tinker
>>> config('ai.provider')
=> "azure"

>>> config('ai.azure.deployment_id')
=> "gpt-4o"

# Test OpenAI Laravel package
>>> use OpenAI\Laravel\Facades\OpenAI;
>>> OpenAI::chat()->create([...])
```

---

## 📚 Documentation

- **Detailed Guide**: `AZURE-OPENAI-SETUP.md`
- **Configuration**: `config/ai.php`
- **Implementation**: `app/Services/AI/*` (Phase 4)

---

## ✅ Next Actions

1. **Add Azure API Key**: Update `.env` with your actual Azure OpenAI key
2. **Implement AI Services**: Create service classes in `app/Services/AI/`
3. **Create AI Usage Table**: Run migration for `ai_usage_logs`
4. **Test Integration**: Write tests for each AI feature

---

**Model**: GPT-4o (Latest GPT-4 Family)  
**Context Window**: 128,000 tokens  
**Max Output**: 4,096 tokens (configurable)  
**Pricing**: $5/1M input tokens, $15/1M output tokens
