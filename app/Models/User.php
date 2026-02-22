<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, TwoFactorAuthenticatable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'account_type',
        'avatar',
        'is_active',
        'preferences',
        'last_login_at',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone' => 'encrypted',
            'preferences' => 'array',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relationships
     */
    
    public function twoFactorAuth()
    {
        return $this->hasOne(TwoFactorAuthentication::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Social authentication accounts
     */
    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }
    
    /**
     * Check if user is a job seeker
     */
    public function isJobSeeker(): bool
    {
        return $this->account_type === 'job_seeker';
    }
    
    /**
     * Check if user is an employer
     */
    public function isEmployer(): bool
    {
        return $this->account_type === 'employer';
    }
    
    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->account_type === 'admin';
    }
    
    /**
     * Get the user's profile
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    
    /**
     * Get the user's company (for employers)
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Get the user's subscription
     */
    public function subscription()
    {
        return $this->hasOne(UserSubscription::class);
    }

    /**
     * Get the user's agent configuration
     */
    public function agentConfiguration()
    {
        return $this->hasOne(AgentConfiguration::class);
    }
    
    /**
     * Get the user's applications
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
    
    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription()
            ->whereIn('status', ['active', 'trialing'])
            ->where('current_period_end', '>', now())
            ->exists();
    }
    
    /**
     * Check if user has a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        $subscription = $this->subscription;
        if (!$subscription || !$subscription->isActive()) {
            $freePlan = SubscriptionPlan::where('slug', 'free')->first();
            return in_array($feature, $freePlan->features ?? []);
        }
        
        $plan = $subscription->subscriptionPlan;
        return in_array($feature, $plan->features);
    }
    
    /**
     * Get remaining applications for current month
     */
    public function getRemainingApplications(): int
    {
        $subscription = $this->subscription;
        if (!$subscription) return 0;
        
        $plan = $subscription->subscriptionPlan;
        if ($plan->applications_limit === null) return PHP_INT_MAX; // Unlimited
        
        $used = $subscription->applications_used_this_month;
        return max(0, $plan->applications_limit - $used);
    }
    
    /**
     * Get remaining AI credits for current month
     */
    public function getRemainingAICredits(): int
    {
        $subscription = $this->subscription;
        if (!$subscription) return 0;
        
        $plan = $subscription->subscriptionPlan;
        if ($plan->ai_credits === null) return PHP_INT_MAX; // Unlimited
        
        $used = $subscription->ai_credits_used_this_month;
        return max(0, $plan->ai_credits - $used);
    }
    
    /**
     * Check if user can apply to jobs
     */
    public function canApplyToJobs(): bool
    {
        return $this->getRemainingApplications() > 0;
    }
    
    /**
     * Check if user has AI credits
     */
    public function hasAICredits(int $required = 1): bool
    {
        return $this->getRemainingAICredits() >= $required;
    }
    
    /**
     * Deduct AI credits from user's subscription
     */
    public function deductAICredits(int $amount = 1): void
    {
        $subscription = $this->subscription;
        if ($subscription) {
            $subscription->increment('ai_credits_used_this_month', $amount);
        }
    }
    
    /**
     * Get user's payment transactions
     */
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
    
    /**
     * Get user's saved jobs
     */
    public function savedJobs()
    {
        return $this->belongsToMany(Job::class, 'saved_jobs')
            ->withTimestamps()
            ->withPivot('notes');
    }
    
    /**
     * Get user's job alerts
     */
    public function jobAlerts()
    {
        return $this->hasMany(JobAlert::class);
    }
    
    /**
     * Get companies the user is following
     */
    public function followedCompanies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withTimestamps();
    }
    
    /**
     * Get user's company reviews
     */
    public function companyReviews()
    {
        return $this->hasMany(CompanyReview::class);
    }
    
    /**
     * Get user's assessment attempts
     */
    public function assessmentAttempts()
    {
        return $this->hasMany(AssessmentAttempt::class);
    }
    
    /**
     * Get user's certificates
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
    
    /**
     * Get user's badges
     */
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'badge_user')
            ->withPivot(['certificate_id', 'earned_at', 'is_visible', 'display_order'])
            ->withTimestamps();
    }
    
    /**
     * Get user's interview sessions
     */
    public function interviewSessions()
    {
        return $this->hasMany(InterviewSession::class);
    }
    
    /**
     * Get user's interview responses
     */
    public function interviewResponses()
    {
        return $this->hasMany(InterviewResponse::class);
    }

    /**
     * Get user's skill gaps
     */
    public function skillGaps()
    {
        return $this->hasMany(SkillGap::class);
    }

    /**
     * Get user's learning paths
     */
    public function learningPaths()
    {
        return $this->hasMany(LearningPath::class);
    }

    /**
     * Get user's skill validations
     */
    public function skillValidations()
    {
        return $this->hasMany(SkillValidation::class);
    }

    /**
     * Get user's skill assessments
     */
    public function skillAssessments()
    {
        return $this->hasMany(SkillAssessment::class);
    }

    // ============================================
    // PROFESSIONAL NETWORKING RELATIONSHIPS
    // ============================================

    /**
     * Get user's candidate profile (alias for profile for networking)
     */
    public function candidateProfile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get connection requests sent by this user
     */
    public function sentConnections()
    {
        return $this->hasMany(Connection::class, 'sender_id');
    }

    /**
     * Get connection requests received by this user
     */
    public function receivedConnections()
    {
        return $this->hasMany(Connection::class, 'recipient_id');
    }

    /**
     * Get all connections (both sent and received, accepted only)
     */
    public function connections()
    {
        return $this->sentConnections()->accepted()
            ->union($this->receivedConnections()->accepted());
    }

    /**
     * Get users this user is following
     */
    public function following()
    {
        return $this->hasMany(UserFollow::class, 'follower_id');
    }

    /**
     * Get users following this user
     */
    public function followers()
    {
        return $this->hasMany(UserFollow::class, 'following_id');
    }

    /**
     * Get user's posts
     */
    public function posts()
    {
        return $this->hasMany(UserPost::class);
    }

    /**
     * Get user's post likes
     */
    public function postLikes()
    {
        return $this->hasMany(PostLike::class);
    }

    /**
     * Get user's comments
     */
    public function postComments()
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * Get groups owned by this user
     */
    public function ownedGroups()
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    /**
     * Get user's group memberships
     */
    public function groupMemberships()
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get groups user is a member of
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get user's network conversations
     */
    public function networkConversations()
    {
        return $this->belongsToMany(NetworkConversation::class, 'conversation_participants', 'user_id', 'conversation_id')
            ->withPivot(['last_read_at', 'is_muted', 'is_pinned'])
            ->withTimestamps();
    }

    /**
     * Get user's sent network messages
     */
    public function networkMessages()
    {
        return $this->hasMany(NetworkMessage::class, 'sender_id');
    }

    /**
     * Get mentorship matches where user is mentor
     */
    public function mentorships()
    {
        return $this->hasMany(MentorshipMatch::class, 'mentor_id');
    }

    /**
     * Get mentorship matches where user is mentee
     */
    public function menteeships()
    {
        return $this->hasMany(MentorshipMatch::class, 'mentee_id');
    }

    /**
     * Get user's resumes
     */
    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    /**
     * Get events organized by user
     */
    public function organizedEvents()
    {
        return $this->hasMany(NetworkEvent::class, 'organizer_id');
    }

    /**
     * Get user's event RSVPs
     */
    public function eventRsvps()
    {
        return $this->hasMany(EventRsvp::class);
    }

    /**
     * Get events user is attending
     */
    public function attendingEvents()
    {
        return $this->belongsToMany(NetworkEvent::class, 'event_rsvps', 'user_id', 'event_id')
            ->wherePivot('status', 'going')
            ->withPivot('status', 'note')
            ->withTimestamps();
    }

    /**
     * Get user's mentor profile
     */
    public function mentorProfile()
    {
        return $this->hasOne(MentorProfile::class);
    }

    /**
     * Get network notification settings
     */
    public function networkNotificationSettings()
    {
        return $this->hasOne(NetworkNotificationSetting::class);
    }

    /**
     * Get negotiation strategies
     */
    public function strategies()
    {
        return $this->hasMany(NegotiationStrategy::class);
    }

    /**
     * Get negotiation sessions
     */
    public function negotiationSessions()
    {
        return $this->hasMany(NegotiationSession::class);
    }

    /**
     * Get avatar URL accessor
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }
}