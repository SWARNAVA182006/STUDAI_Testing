{{--
================================================================================
STUDAI PATH — COMPLETE BRAND CONTENT SYSTEM
================================================================================
India's First Autonomous Career OS
Version: 1.0
Last Updated: November 2025
================================================================================
--}}

{{-- ============================================================================
SECTION 1: HOMEPAGE CONTENT
============================================================================ --}}

@php
$homepage = [
    'hero' => [
        'headline' => 'Your Career. On Autopilot.',
        'subheadline' => 'StudAI Path is India\'s first Autonomous Career OS. It finds jobs, applies for you, preps interviews, negotiates salaries, and builds your entire career path — powered by AI.',
        'cta_primary' => 'Start Free — No Credit Card',
        'cta_secondary' => 'Watch Demo',
        'trust_badge' => 'Trusted by 50,000+ professionals across India',
    ],
    
    'search_bar' => [
        'placeholder' => 'Search jobs, skills, companies, or ask AI anything...',
        'voice_prompt' => 'Try: "Find remote React jobs in Bangalore paying 20L+"',
    ],
    
    'stats' => [
        ['value' => '50K+', 'label' => 'Careers Launched'],
        ['value' => '2.5M', 'label' => 'Jobs Indexed'],
        ['value' => '94%', 'label' => 'Interview Success'],
        ['value' => '40%', 'label' => 'Avg. Salary Increase'],
    ],
    
    'features' => [
        [
            'icon' => 'robot',
            'title' => 'Autonomous Agent',
            'description' => 'Set your preferences. Our AI applies to 100+ matching jobs daily — while you sleep.',
            'cta' => 'Activate Agent',
        ],
        [
            'icon' => 'search',
            'title' => 'Smart Job Search',
            'description' => 'AI-powered matching. No endless scrolling. Just perfect-fit opportunities.',
            'cta' => 'Find Jobs',
        ],
        [
            'icon' => 'document',
            'title' => 'Resume Studio',
            'description' => 'ATS-optimized resumes that actually get callbacks. Built in 5 minutes.',
            'cta' => 'Build Resume',
        ],
        [
            'icon' => 'video',
            'title' => 'Interview AI',
            'description' => 'Practice with AI interviewers. Get real-time feedback. Walk in confident.',
            'cta' => 'Start Practice',
        ],
        [
            'icon' => 'chart',
            'title' => 'Market Intelligence',
            'description' => 'Real-time salary data, skill demand trends, and career forecasts.',
            'cta' => 'Explore Insights',
        ],
        [
            'icon' => 'shield',
            'title' => 'S.C.O.U.T. for Employers',
            'description' => 'Bias-free AI hiring. Find the best talent faster. Reduce time-to-hire by 60%.',
            'cta' => 'For Employers',
        ],
    ],
    
    'value_props' => [
        'title' => 'Why 50,000+ Professionals Choose StudAI Path',
        'items' => [
            [
                'title' => 'Zero Manual Applications',
                'description' => 'Our AI agent handles everything. From finding matches to submitting applications — fully autonomous.',
            ],
            [
                'title' => 'Interview-Ready in Hours',
                'description' => 'AI mock interviews tailored to your target role. Real questions. Real-time coaching.',
            ],
            [
                'title' => 'Salary Negotiation AI',
                'description' => 'Know your worth. Our AI analyzes market data and coaches you through negotiations.',
            ],
            [
                'title' => 'One Profile. Infinite Reach.',
                'description' => 'Apply across platforms, companies, and countries — with a single unified career profile.',
            ],
        ],
    ],
    
    'testimonials' => [
        [
            'quote' => 'StudAI Path got me 3 offers in 2 weeks. The autonomous agent is like having a full-time job search assistant.',
            'name' => 'Priya Sharma',
            'role' => 'Senior Software Engineer',
            'company' => 'Google',
            'image' => 'priya.jpg',
        ],
        [
            'quote' => 'The interview AI helped me crack my Amazon SDE-2 interview. The behavioral prep was spot-on.',
            'name' => 'Rahul Menon',
            'role' => 'SDE-2',
            'company' => 'Amazon',
            'image' => 'rahul.jpg',
        ],
        [
            'quote' => 'As a recruiter, S.C.O.U.T. changed how we hire. Time-to-hire dropped 60%. Quality went up.',
            'name' => 'Anjali Verma',
            'role' => 'Head of Talent',
            'company' => 'Razorpay',
            'image' => 'anjali.jpg',
        ],
    ],
    
    'faqs' => [
        [
            'question' => 'What is an Autonomous Career OS?',
            'answer' => 'StudAI Path is a unified system that manages your entire career lifecycle — from job discovery to salary negotiation — using AI agents that work 24/7 on your behalf.',
        ],
        [
            'question' => 'Is the autonomous job application really automatic?',
            'answer' => 'Yes. Once you set your preferences (role, salary, location, company type), our AI agent automatically finds and applies to matching jobs. You can review applications anytime.',
        ],
        [
            'question' => 'How is this different from LinkedIn or Naukri?',
            'answer' => 'Job boards show listings. StudAI Path actively works for you — finding jobs, applying, prepping interviews, and negotiating offers. It\'s the difference between a library and a personal assistant.',
        ],
        [
            'question' => 'What is S.C.O.U.T.?',
            'answer' => 'S.C.O.U.T. (Smart Candidate Optimization & Unified Tracking) is our AI-powered ATS for employers. It removes hiring bias, automates screening, and identifies top candidates faster.',
        ],
        [
            'question' => 'Is there a free plan?',
            'answer' => 'Yes. Our free tier includes unlimited job search, basic resume builder, and 5 AI interview practice sessions per month. No credit card required.',
        ],
    ],
];

$footer = [
    'tagline' => 'Your Career. Autonomous.',
    'description' => 'StudAI Path is building the future of career management. One AI-powered decision at a time.',
    
    'columns' => [
        'Product' => [
            'Job Search' => '/jobs',
            'Autonomous Agent' => '/agent',
            'Resume Builder' => '/resume',
            'Interview AI' => '/interview',
            'Market Intelligence' => '/analytics',
            'Career Coach' => '/career',
        ],
        'For Employers' => [
            'S.C.O.U.T. AI' => '/employer/scout',
            'Post Jobs' => '/employer/jobs/create',
            'Talent Search' => '/employer/talent',
            'Pricing' => '/employer/pricing',
            'Enterprise' => '/enterprise',
        ],
        'Marketplace' => [
            'Find Freelancers' => '/marketplace',
            'Post Projects' => '/marketplace/post',
            'Become a Freelancer' => '/marketplace/join',
            'Escrow Payments' => '/marketplace/escrow',
        ],
        'Company' => [
            'About StudAI' => '/about',
            'Careers' => '/careers',
            'Blog' => '/blog',
            'Press' => '/press',
            'Contact' => '/contact',
        ],
        'Legal' => [
            'Privacy Policy' => '/privacy',
            'Terms of Service' => '/terms',
            'Cookie Policy' => '/cookies',
            'Security' => '/security',
        ],
    ],
    
    'social' => [
        'twitter' => 'https://twitter.com/studaipath',
        'linkedin' => 'https://linkedin.com/company/studai',
        'instagram' => 'https://instagram.com/studaipath',
        'youtube' => 'https://youtube.com/@studaipath',
    ],
    
    'copyright' => '© 2025 StudAI Technologies Pvt. Ltd. All rights reserved.',
    'made_in' => 'Made with ❤️ in India',
];
@endphp


{{-- ============================================================================
SECTION 2: PRODUCT PAGES
============================================================================ --}}

@php
$products = [
    'overview' => [
        'hero' => [
            'title' => 'One OS. Your Entire Career.',
            'subtitle' => 'StudAI Path unifies every aspect of career growth into one intelligent, autonomous system.',
            'cta' => 'Explore the Platform',
        ],
        'intro' => 'StudAI Path isn\'t just another job portal. It\'s a Career Operating System — a unified platform where AI agents manage your job search, applications, interviews, and career growth. Automatically.',
        
        'who_its_for' => [
            [
                'persona' => 'Job Seekers',
                'tagline' => 'Stop hunting. Start receiving.',
                'description' => 'Let AI find, apply, and negotiate for you. Focus on what matters — growing your skills.',
            ],
            [
                'persona' => 'Freelancers',
                'tagline' => 'Your work. Your terms.',
                'description' => 'Find projects, manage clients, handle payments. All in one place.',
            ],
            [
                'persona' => 'Employers',
                'tagline' => 'Hire better. Hire faster.',
                'description' => 'S.C.O.U.T. AI eliminates bias and surfaces top talent in minutes, not months.',
            ],
        ],
        
        'modules' => [
            [
                'name' => 'Autonomous Agent',
                'tagline' => 'Your 24/7 career manager',
                'description' => 'Set preferences. Sit back. Our AI applies to 100+ jobs daily, tracks responses, and optimizes your strategy.',
            ],
            [
                'name' => 'Smart Job Search',
                'tagline' => 'AI-matched opportunities',
                'description' => 'No more endless scrolling. Get curated jobs that actually fit your skills, goals, and preferences.',
            ],
            [
                'name' => 'Resume Studio',
                'tagline' => 'ATS-beating resumes',
                'description' => '5-minute resume builder with AI optimization. 94% callback rate improvement.',
            ],
            [
                'name' => 'Interview AI',
                'tagline' => 'Practice makes perfect',
                'description' => 'Role-specific mock interviews. Behavioral coaching. Real-time feedback. Walk in confident.',
            ],
            [
                'name' => 'Market Intelligence',
                'tagline' => 'Know your worth',
                'description' => 'Real-time salary benchmarks, skill demand trends, and career path forecasting.',
            ],
            [
                'name' => 'Career Coach',
                'tagline' => 'Your AI mentor',
                'description' => 'Personalized learning paths, skill gap analysis, and growth recommendations.',
            ],
        ],
    ],
    
    'job_seeker' => [
        'hero' => [
            'title' => 'Job Search. Reimagined.',
            'subtitle' => 'Stop browsing job boards. Let AI bring opportunities to you.',
            'cta' => 'Start Searching',
        ],
        'features' => [
            [
                'title' => 'AI-Powered Matching',
                'description' => 'Our algorithm analyzes 50+ factors to find roles that truly fit — not just keyword matches.',
            ],
            [
                'title' => 'One-Click Apply',
                'description' => 'Unified profile means instant applications. No more re-entering the same information.',
            ],
            [
                'title' => 'Application Tracking',
                'description' => 'Real-time status updates. Know exactly where each application stands.',
            ],
            [
                'title' => 'Salary Insights',
                'description' => 'See market rates before you apply. Negotiate from a position of knowledge.',
            ],
        ],
        'use_cases' => [
            'Fresh graduates entering the workforce',
            'Professionals seeking career transitions',
            'Tech workers looking for remote opportunities',
            'Experienced leaders pursuing executive roles',
        ],
    ],
    
    'autonomous_agent' => [
        'hero' => [
            'title' => 'Your Career Runs 24/7.',
            'subtitle' => 'The Autonomous Agent applies to jobs while you sleep, learn, or live your life.',
            'cta' => 'Activate Your Agent',
        ],
        'how_it_works' => [
            [
                'step' => '01',
                'title' => 'Set Preferences',
                'description' => 'Tell us your ideal role, salary range, location, and company culture preferences.',
            ],
            [
                'step' => '02',
                'title' => 'Agent Activates',
                'description' => 'Our AI scans millions of openings across platforms, identifying perfect matches.',
            ],
            [
                'step' => '03',
                'title' => 'Auto-Apply',
                'description' => 'The agent applies with tailored cover letters and optimized profiles.',
            ],
            [
                'step' => '04',
                'title' => 'You Interview',
                'description' => 'When employers respond, you step in for interviews — fully prepared.',
            ],
        ],
        'stats' => [
            ['value' => '100+', 'label' => 'Applications per day'],
            ['value' => '85%', 'label' => 'Time saved'],
            ['value' => '3x', 'label' => 'More interviews'],
            ['value' => '2 weeks', 'label' => 'Avg. time to offer'],
        ],
    ],
    
    'resume_builder' => [
        'hero' => [
            'title' => 'Resumes That Get Callbacks.',
            'subtitle' => 'ATS-optimized. AI-written. Interview-generating.',
            'cta' => 'Build Your Resume',
        ],
        'features' => [
            [
                'title' => 'ATS Optimization Score',
                'description' => 'Real-time scoring against applicant tracking systems. Know your resume passes before you apply.',
            ],
            [
                'title' => 'AI Content Writer',
                'description' => 'Turn bullet points into impactful achievements. AI writes, you approve.',
            ],
            [
                'title' => '50+ Professional Templates',
                'description' => 'Industry-specific designs that hiring managers actually prefer.',
            ],
            [
                'title' => 'Multi-Format Export',
                'description' => 'PDF, DOCX, or direct link. Optimized for every platform.',
            ],
        ],
        'guarantee' => 'Resume not getting callbacks? We\'ll rewrite it free.',
    ],
    
    'interview_ai' => [
        'hero' => [
            'title' => 'Interview Like You\'ve Done It 100 Times.',
            'subtitle' => 'AI mock interviews that prepare you for the real thing.',
            'cta' => 'Start Practicing',
        ],
        'interview_types' => [
            [
                'type' => 'Behavioral',
                'description' => 'STAR method coaching. Perfect for HR rounds.',
            ],
            [
                'type' => 'Technical',
                'description' => 'Coding, system design, and domain-specific questions.',
            ],
            [
                'type' => 'Case Study',
                'description' => 'Consulting-style frameworks and problem solving.',
            ],
            [
                'type' => 'Executive',
                'description' => 'Leadership scenarios and strategic thinking.',
            ],
        ],
        'feedback' => [
            'Real-time AI analysis of your responses',
            'Body language and tone assessment (video)',
            'Personalized improvement suggestions',
            'Industry benchmark comparisons',
        ],
    ],
    
    'market_intelligence' => [
        'hero' => [
            'title' => 'Career Decisions. Data-Driven.',
            'subtitle' => 'Real-time salary data, skill demand trends, and market insights.',
            'cta' => 'Explore Insights',
        ],
        'data_points' => [
            [
                'title' => 'Salary Benchmarks',
                'description' => 'Know exactly what your role pays — by location, experience, and company size.',
            ],
            [
                'title' => 'Skill Demand Trends',
                'description' => 'See which skills are rising, stable, or declining. Stay ahead of the curve.',
            ],
            [
                'title' => 'Career Path Forecasts',
                'description' => 'AI-predicted trajectories based on millions of career progressions.',
            ],
            [
                'title' => 'Company Insights',
                'description' => 'Hiring trends, growth rates, and employee sentiment by company.',
            ],
        ],
    ],
    
    'career_coach' => [
        'hero' => [
            'title' => 'Your Personal AI Career Mentor.',
            'subtitle' => 'Skill gaps identified. Learning paths created. Growth accelerated.',
            'cta' => 'Meet Your Coach',
        ],
        'capabilities' => [
            [
                'title' => 'Skill Gap Analysis',
                'description' => 'Compare your profile to your dream role. See exactly what\'s missing.',
            ],
            [
                'title' => 'Personalized Learning Paths',
                'description' => 'Curated courses, certifications, and projects to close skill gaps.',
            ],
            [
                'title' => 'Career Roadmaps',
                'description' => 'Year-by-year growth plans based on your goals and market trends.',
            ],
            [
                'title' => 'Mentorship Matching',
                'description' => 'Connect with industry professionals who\'ve walked your path.',
            ],
        ],
    ],
    
    'marketplace' => [
        'hero' => [
            'title' => 'Freelance Without the Chaos.',
            'subtitle' => 'Find projects. Deliver work. Get paid. All protected.',
            'cta' => 'Explore Projects',
        ],
        'for_freelancers' => [
            [
                'title' => 'Curated Projects',
                'description' => 'Quality clients, fair budgets, clear scopes. No race-to-the-bottom bidding.',
            ],
            [
                'title' => 'Secure Payments',
                'description' => 'Escrow protection on every project. Get paid for the work you do.',
            ],
            [
                'title' => 'Reputation Building',
                'description' => 'Verified reviews and skill assessments that travel with you.',
            ],
        ],
        'for_clients' => [
            [
                'title' => 'Verified Talent',
                'description' => 'Every freelancer is skill-tested and background-verified.',
            ],
            [
                'title' => 'AI Matching',
                'description' => 'Describe your project. We surface the perfect freelancers.',
            ],
            [
                'title' => 'Protected Transactions',
                'description' => 'Pay only when milestones are delivered. Full dispute resolution.',
            ],
        ],
    ],
];
@endphp


{{-- ============================================================================
SECTION 3: EMPLOYER / ATS PAGES
============================================================================ --}}

@php
$employer = [
    'scout' => [
        'hero' => [
            'title' => 'Hire Better. Hire Faster. Hire Fair.',
            'subtitle' => 'S.C.O.U.T. AI is the bias-free Applicant Tracking System that surfaces top talent in minutes.',
            'cta' => 'Request Demo',
            'secondary_cta' => 'Start Free Trial',
        ],
        'what_is_scout' => 'S.C.O.U.T. (Smart Candidate Optimization & Unified Tracking) is an AI-powered ATS that removes human bias from hiring, automates candidate screening, and helps you find the best people — not just the best resumes.',
        
        'features' => [
            [
                'title' => 'Bias-Free Screening',
                'description' => 'Our AI evaluates candidates on skills and potential, not names, photos, or backgrounds.',
                'stat' => '73% more diverse hires',
            ],
            [
                'title' => 'Instant Candidate Ranking',
                'description' => 'AI scores every applicant against job requirements. Top talent surfaces immediately.',
                'stat' => '90% screening time saved',
            ],
            [
                'title' => 'One-Click Job Posting',
                'description' => 'Post to 50+ job boards instantly. Manage all applications in one dashboard.',
                'stat' => '50+ job boards',
            ],
            [
                'title' => 'Interview Scheduling AI',
                'description' => 'Automated scheduling that respects everyone\'s calendars. No back-and-forth emails.',
                'stat' => '10 hours saved per hire',
            ],
            [
                'title' => 'Collaborative Hiring',
                'description' => 'Structured feedback, scorecards, and team alignment on every candidate.',
                'stat' => '2x faster decisions',
            ],
            [
                'title' => 'Compliance & Reporting',
                'description' => 'Built-in EEO compliance, GDPR handling, and comprehensive hiring analytics.',
                'stat' => '100% audit-ready',
            ],
        ],
        
        'roi' => [
            'title' => 'The S.C.O.U.T. Advantage',
            'stats' => [
                ['value' => '60%', 'label' => 'Faster time-to-hire'],
                ['value' => '45%', 'label' => 'Lower cost-per-hire'],
                ['value' => '73%', 'label' => 'More diverse candidates'],
                ['value' => '3x', 'label' => 'Better retention at 1 year'],
            ],
        ],
        
        'workflow' => [
            [
                'step' => '01',
                'title' => 'Create Job',
                'description' => 'AI-assisted job descriptions that attract the right candidates.',
            ],
            [
                'step' => '02',
                'title' => 'Distribute',
                'description' => 'One click publishes to 50+ platforms and your career page.',
            ],
            [
                'step' => '03',
                'title' => 'AI Screens',
                'description' => 'Candidates ranked by fit. Bias removed. Top talent highlighted.',
            ],
            [
                'step' => '04',
                'title' => 'Interview',
                'description' => 'Automated scheduling, structured interviews, and team scorecards.',
            ],
            [
                'step' => '05',
                'title' => 'Hire',
                'description' => 'Offer management, e-signatures, and onboarding — all in one place.',
            ],
        ],
    ],
    
    'dashboard' => [
        'hero' => [
            'title' => 'Your Hiring Command Center.',
            'subtitle' => 'Every open role, every candidate, every metric — in one intelligent dashboard.',
        ],
        'features' => [
            'Real-time pipeline visualization',
            'Team activity and collaboration feeds',
            'Hiring velocity and bottleneck detection',
            'Budget tracking and forecasting',
            'Diversity and inclusion metrics',
        ],
    ],
    
    'job_posting' => [
        'hero' => [
            'title' => 'Write Job Posts That Attract Top Talent.',
            'subtitle' => 'AI-powered job description builder with bias detection and SEO optimization.',
        ],
        'features' => [
            [
                'title' => 'AI Writing Assistant',
                'description' => 'Generate compelling job descriptions in seconds. Edit with AI suggestions.',
            ],
            [
                'title' => 'Bias Detection',
                'description' => 'Real-time scanning for gendered language and exclusionary terms.',
            ],
            [
                'title' => 'Multi-Platform Publish',
                'description' => 'One click distributes to LinkedIn, Indeed, Naukri, and 50+ more.',
            ],
            [
                'title' => 'Performance Analytics',
                'description' => 'Track views, applies, and conversion by source. Optimize continuously.',
            ],
        ],
    ],
    
    'candidate_review' => [
        'hero' => [
            'title' => 'Review Candidates 10x Faster.',
            'subtitle' => 'AI-powered screening that surfaces the best without the bias.',
        ],
        'features' => [
            [
                'title' => 'Smart Ranking',
                'description' => 'Candidates scored on skills, experience, and cultural indicators.',
            ],
            [
                'title' => 'Resume Parsing',
                'description' => 'Structured data extraction. No more manual entry.',
            ],
            [
                'title' => 'Video Screening',
                'description' => 'Async video responses with AI analysis and transcription.',
            ],
            [
                'title' => 'Kanban Pipeline',
                'description' => 'Drag-and-drop candidates through your hiring stages.',
            ],
        ],
    ],
];
@endphp


{{-- ============================================================================
SECTION 4: PRICING PAGE
============================================================================ --}}

@php
$pricing = [
    'hero' => [
        'title' => 'Simple Pricing. Powerful Results.',
        'subtitle' => 'Start free. Scale as you grow. No hidden fees.',
    ],
    
    'toggle' => [
        'job_seekers' => 'For Job Seekers',
        'employers' => 'For Employers',
    ],
    
    'job_seeker_plans' => [
        [
            'name' => 'Free',
            'price' => '₹0',
            'period' => 'forever',
            'description' => 'Everything you need to start your job search.',
            'cta' => 'Get Started Free',
            'features' => [
                'Unlimited job search',
                'Basic resume builder',
                '5 AI interview sessions/month',
                'Application tracking',
                'Basic salary insights',
            ],
            'highlighted' => false,
        ],
        [
            'name' => 'Pro',
            'price' => '₹499',
            'period' => '/month',
            'description' => 'Accelerate your career with AI automation.',
            'cta' => 'Start Pro Trial',
            'features' => [
                'Everything in Free',
                'Autonomous Agent (50 apps/day)',
                'Unlimited AI interviews',
                'Premium resume templates',
                'Salary negotiation coach',
                'Priority support',
            ],
            'highlighted' => true,
            'badge' => 'Most Popular',
        ],
        [
            'name' => 'Executive',
            'price' => '₹1,999',
            'period' => '/month',
            'description' => 'For senior professionals and career changers.',
            'cta' => 'Go Executive',
            'features' => [
                'Everything in Pro',
                'Unlimited agent applications',
                'Executive resume service',
                '1:1 career coaching sessions',
                'LinkedIn profile optimization',
                'Dedicated success manager',
            ],
            'highlighted' => false,
        ],
    ],
    
    'employer_plans' => [
        [
            'name' => 'Starter',
            'price' => '₹0',
            'period' => 'forever',
            'description' => 'For small teams making their first hires.',
            'cta' => 'Start Hiring Free',
            'features' => [
                '3 active job posts',
                'Basic candidate management',
                'Email notifications',
                'Standard job board distribution',
            ],
            'highlighted' => false,
        ],
        [
            'name' => 'Growth',
            'price' => '₹4,999',
            'period' => '/month',
            'description' => 'For growing companies scaling their teams.',
            'cta' => 'Start Growth Trial',
            'features' => [
                'Unlimited job posts',
                'S.C.O.U.T. AI screening',
                'Team collaboration tools',
                '50+ job board distribution',
                'Interview scheduling',
                'Basic analytics',
            ],
            'highlighted' => true,
            'badge' => 'Best Value',
        ],
        [
            'name' => 'Enterprise',
            'price' => 'Custom',
            'period' => '',
            'description' => 'For large organizations with complex needs.',
            'cta' => 'Contact Sales',
            'features' => [
                'Everything in Growth',
                'Custom integrations (HRIS, ATS)',
                'Dedicated account manager',
                'Advanced analytics & reporting',
                'SSO & advanced security',
                'Custom SLAs',
                'On-premise deployment option',
            ],
            'highlighted' => false,
        ],
    ],
    
    'comparison_note' => 'All plans include our core platform. No setup fees. Cancel anytime.',
    
    'faqs' => [
        [
            'question' => 'Can I switch plans anytime?',
            'answer' => 'Yes. Upgrade or downgrade at any time. Changes take effect immediately, and we prorate accordingly.',
        ],
        [
            'question' => 'Is there a free trial for paid plans?',
            'answer' => 'Yes. All paid plans include a 14-day free trial. No credit card required to start.',
        ],
        [
            'question' => 'What payment methods do you accept?',
            'answer' => 'We accept all major credit cards, debit cards, UPI, and net banking. Enterprise plans can pay via invoice.',
        ],
        [
            'question' => 'Is my data secure?',
            'answer' => 'Absolutely. We use bank-grade encryption, are SOC 2 compliant, and never sell your data.',
        ],
    ],
];
@endphp


{{-- ============================================================================
SECTION 5: SUPPORT PAGES
============================================================================ --}}

@php
$support = [
    'help_center' => [
        'hero' => [
            'title' => 'How can we help?',
            'subtitle' => 'Search our knowledge base or browse categories below.',
            'search_placeholder' => 'Search for answers...',
        ],
        'categories' => [
            [
                'title' => 'Getting Started',
                'description' => 'Set up your account and start your journey.',
                'icon' => 'rocket',
                'articles' => 24,
            ],
            [
                'title' => 'Job Search & Applications',
                'description' => 'Find jobs and manage applications.',
                'icon' => 'search',
                'articles' => 31,
            ],
            [
                'title' => 'Autonomous Agent',
                'description' => 'Configure and optimize your AI agent.',
                'icon' => 'robot',
                'articles' => 18,
            ],
            [
                'title' => 'Resume & Profile',
                'description' => 'Build and optimize your professional profile.',
                'icon' => 'document',
                'articles' => 22,
            ],
            [
                'title' => 'Interview Preparation',
                'description' => 'Practice and ace your interviews.',
                'icon' => 'video',
                'articles' => 15,
            ],
            [
                'title' => 'Billing & Account',
                'description' => 'Manage subscriptions and settings.',
                'icon' => 'credit-card',
                'articles' => 12,
            ],
        ],
        'popular_articles' => [
            'How to activate the Autonomous Agent',
            'Optimizing your resume for ATS',
            'Setting up job preferences',
            'Understanding your AI interview feedback',
            'Changing your subscription plan',
        ],
        'contact_cta' => 'Can\'t find what you need? Contact our support team.',
    ],
    
    'contact' => [
        'hero' => [
            'title' => 'Let\'s Talk.',
            'subtitle' => 'Have questions? We\'re here to help.',
        ],
        'options' => [
            [
                'title' => 'Chat with Us',
                'description' => 'Get instant answers from our AI assistant, or connect with a human.',
                'cta' => 'Start Chat',
                'availability' => '24/7 AI • 9 AM - 9 PM IST for humans',
            ],
            [
                'title' => 'Email Support',
                'description' => 'Detailed questions? Send us an email and we\'ll respond within 24 hours.',
                'cta' => 'support@studai.in',
                'availability' => 'Response within 24 hours',
            ],
            [
                'title' => 'Enterprise Sales',
                'description' => 'Looking to implement StudAI Path for your organization?',
                'cta' => 'Contact Sales',
                'availability' => 'Dedicated account team',
            ],
        ],
        'office' => [
            'title' => 'Our Office',
            'address' => 'StudAI Technologies Pvt. Ltd.',
            'line1' => '91 Springboard, Koramangala',
            'line2' => 'Bangalore, Karnataka 560034',
            'line3' => 'India',
        ],
    ],
    
    'onboarding_job_seeker' => [
        'title' => 'Welcome to StudAI Path',
        'subtitle' => 'Let\'s set up your Career OS in 5 minutes.',
        'steps' => [
            [
                'step' => 1,
                'title' => 'Complete Your Profile',
                'description' => 'Add your experience, skills, and education. Our AI will handle the rest.',
                'duration' => '2 min',
            ],
            [
                'step' => 2,
                'title' => 'Set Job Preferences',
                'description' => 'Tell us your ideal role, salary, location, and company type.',
                'duration' => '1 min',
            ],
            [
                'step' => 3,
                'title' => 'Upload or Build Resume',
                'description' => 'Import an existing resume or create one with our AI builder.',
                'duration' => '2 min',
            ],
            [
                'step' => 4,
                'title' => 'Activate Your Agent',
                'description' => 'Turn on the Autonomous Agent and let AI start applying for you.',
                'duration' => '30 sec',
            ],
        ],
        'completion_message' => 'You\'re all set! Your Career OS is now running.',
    ],
    
    'onboarding_employer' => [
        'title' => 'Welcome to S.C.O.U.T.',
        'subtitle' => 'Start hiring smarter in 10 minutes.',
        'steps' => [
            [
                'step' => 1,
                'title' => 'Set Up Company Profile',
                'description' => 'Add your company details, culture, and branding.',
                'duration' => '3 min',
            ],
            [
                'step' => 2,
                'title' => 'Invite Your Team',
                'description' => 'Add hiring managers and recruiters to collaborate.',
                'duration' => '2 min',
            ],
            [
                'step' => 3,
                'title' => 'Post Your First Job',
                'description' => 'Use our AI to create a compelling job description.',
                'duration' => '3 min',
            ],
            [
                'step' => 4,
                'title' => 'Configure S.C.O.U.T.',
                'description' => 'Set your screening criteria and preferences.',
                'duration' => '2 min',
            ],
        ],
        'completion_message' => 'Ready to hire! Candidates will start flowing in.',
    ],
];
@endphp


{{-- ============================================================================
SECTION 6: SYSTEM TEXT (MICROCOPY)
============================================================================ --}}

@php
$microcopy = [
    'buttons' => [
        // Primary Actions
        'get_started' => 'Get Started Free',
        'sign_up' => 'Create Account',
        'sign_in' => 'Sign In',
        'log_out' => 'Log Out',
        'continue' => 'Continue',
        'next' => 'Next',
        'back' => 'Back',
        'submit' => 'Submit',
        'save' => 'Save',
        'save_changes' => 'Save Changes',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'done' => 'Done',
        
        // Job Actions
        'apply_now' => 'Apply Now',
        'quick_apply' => 'Quick Apply',
        'save_job' => 'Save Job',
        'view_job' => 'View Details',
        'search_jobs' => 'Search Jobs',
        
        // Agent Actions
        'activate_agent' => 'Activate Agent',
        'pause_agent' => 'Pause Agent',
        'configure_agent' => 'Configure',
        
        // Resume Actions
        'build_resume' => 'Build Resume',
        'upload_resume' => 'Upload Resume',
        'download_pdf' => 'Download PDF',
        'preview' => 'Preview',
        
        // Interview Actions
        'start_practice' => 'Start Practice',
        'schedule_interview' => 'Schedule Interview',
        'view_feedback' => 'View Feedback',
        
        // Employer Actions
        'post_job' => 'Post Job',
        'view_candidates' => 'View Candidates',
        'schedule' => 'Schedule',
        'send_offer' => 'Send Offer',
        'reject' => 'Reject',
        'shortlist' => 'Shortlist',
    ],
    
    'form_placeholders' => [
        'email' => 'you@example.com',
        'password' => 'Enter your password',
        'name' => 'Your full name',
        'phone' => '+91 98765 43210',
        'job_title' => 'e.g., Senior Software Engineer',
        'company' => 'e.g., Google',
        'location' => 'e.g., Bangalore, India',
        'salary_expectation' => 'e.g., ₹20,00,000 - ₹25,00,000',
        'skills' => 'e.g., React, Python, AWS',
        'experience_years' => 'e.g., 5',
        'search_jobs' => 'Search by title, skill, or company...',
        'search_candidates' => 'Search by name, skill, or keyword...',
        'cover_letter' => 'Tell the employer why you\'re a great fit...',
        'job_description' => 'Describe the role, responsibilities, and requirements...',
    ],
    
    'onboarding_steps' => [
        'welcome' => 'Welcome to StudAI Path! Let\'s get you set up.',
        'profile_start' => 'First, let\'s learn about you.',
        'profile_complete' => 'Great! Your profile is looking good.',
        'preferences_start' => 'Now, tell us about your ideal job.',
        'preferences_complete' => 'Perfect. We\'ll find jobs that match.',
        'resume_prompt' => 'Upload a resume or create one with AI.',
        'resume_complete' => 'Your resume is ready to impress.',
        'agent_prompt' => 'Ready to activate your AI agent?',
        'all_done' => 'You\'re all set! Your Career OS is running.',
    ],
    
    'toast_notifications' => [
        // Success
        'profile_saved' => 'Profile saved successfully.',
        'resume_uploaded' => 'Resume uploaded and analyzed.',
        'application_sent' => 'Application submitted!',
        'job_saved' => 'Job saved to your list.',
        'agent_activated' => 'Autonomous Agent is now active.',
        'agent_paused' => 'Agent paused. Resume anytime.',
        'settings_updated' => 'Settings updated.',
        'password_changed' => 'Password changed successfully.',
        'interview_scheduled' => 'Interview scheduled!',
        
        // Info
        'agent_applying' => 'Your agent is applying to jobs...',
        'new_matches' => 'We found new job matches for you.',
        'interview_reminder' => 'You have an interview in 1 hour.',
        
        // Warning
        'profile_incomplete' => 'Complete your profile for better matches.',
        'resume_outdated' => 'Your resume hasn\'t been updated in 30 days.',
        'agent_limit_reached' => 'Daily application limit reached.',
        
        // Error
        'application_failed' => 'Application failed. Please try again.',
        'upload_failed' => 'Upload failed. Check file size and format.',
        'network_error' => 'Connection lost. Retrying...',
        'session_expired' => 'Session expired. Please sign in again.',
    ],
    
    'empty_states' => [
        'no_jobs' => [
            'title' => 'No jobs found',
            'description' => 'Try adjusting your filters or expanding your search.',
            'cta' => 'Clear Filters',
        ],
        'no_applications' => [
            'title' => 'No applications yet',
            'description' => 'Start applying to jobs or activate your agent.',
            'cta' => 'Find Jobs',
        ],
        'no_saved_jobs' => [
            'title' => 'No saved jobs',
            'description' => 'Save jobs you\'re interested in to review later.',
            'cta' => 'Browse Jobs',
        ],
        'no_interviews' => [
            'title' => 'No upcoming interviews',
            'description' => 'Your scheduled interviews will appear here.',
            'cta' => 'Practice with AI',
        ],
        'no_messages' => [
            'title' => 'No messages yet',
            'description' => 'Messages from employers will appear here.',
            'cta' => 'Update Profile',
        ],
        'no_candidates' => [
            'title' => 'No candidates yet',
            'description' => 'Candidates will appear once applications come in.',
            'cta' => 'Share Job Posting',
        ],
    ],
    
    'error_messages' => [
        'required_field' => 'This field is required.',
        'invalid_email' => 'Please enter a valid email address.',
        'password_weak' => 'Password must be at least 8 characters with a number.',
        'password_mismatch' => 'Passwords don\'t match.',
        'file_too_large' => 'File exceeds maximum size of 5MB.',
        'invalid_file_type' => 'Please upload a PDF, DOC, or DOCX file.',
        'login_failed' => 'Invalid email or password.',
        'account_locked' => 'Account temporarily locked. Try again in 15 minutes.',
        'server_error' => 'Something went wrong. Please try again.',
        'not_found' => 'We couldn\'t find what you\'re looking for.',
        'permission_denied' => 'You don\'t have access to this resource.',
    ],
    
    'success_messages' => [
        'account_created' => 'Account created! Check your email to verify.',
        'email_verified' => 'Email verified successfully.',
        'application_submitted' => 'Your application has been submitted.',
        'resume_created' => 'Resume created and ready to use.',
        'interview_completed' => 'Great practice session! Check your feedback.',
        'offer_accepted' => 'Congratulations! Offer accepted.',
        'job_posted' => 'Your job is now live and accepting applications.',
    ],
    
    'tooltips' => [
        'ai_score' => 'AI match score based on skills, experience, and preferences.',
        'ats_score' => 'How well your resume passes applicant tracking systems.',
        'agent_status' => 'Your agent is actively applying to matching jobs.',
        'salary_insight' => 'Based on market data for this role and location.',
        'interview_feedback' => 'AI analysis of your response quality and delivery.',
        'bias_free' => 'This candidate was evaluated without demographic factors.',
    ],
    
    'ai_suggestions' => [
        'resume_tip' => 'Add quantified achievements to stand out.',
        'skill_gap' => 'Consider learning this skill — it\'s in high demand.',
        'salary_insight' => 'Based on market data, you could negotiate 15% higher.',
        'interview_prep' => 'This company often asks behavioral questions.',
        'agent_optimization' => 'Expanding to remote roles could double your matches.',
    ],
    
    'labels' => [
        // Status
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'applied' => 'Applied',
        'reviewing' => 'In Review',
        'shortlisted' => 'Shortlisted',
        'rejected' => 'Not Selected',
        'hired' => 'Hired',
        
        // Job Types
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contract' => 'Contract',
        'freelance' => 'Freelance',
        'internship' => 'Internship',
        
        // Work Modes
        'remote' => 'Remote',
        'hybrid' => 'Hybrid',
        'onsite' => 'On-site',
        
        // Experience
        'entry' => 'Entry Level',
        'mid' => 'Mid Level',
        'senior' => 'Senior',
        'lead' => 'Lead / Manager',
        'executive' => 'Executive',
    ],
];
@endphp


{{-- ============================================================================
SECTION 7: SEO CONTENT
============================================================================ --}}

@php
$seo = [
    'homepage' => [
        'title' => 'StudAI Path — India\'s First Autonomous Career OS | AI Job Search & Hiring',
        'description' => 'StudAI Path is an AI-powered Career OS that finds jobs, applies automatically, preps interviews, and negotiates salaries. For job seekers, freelancers & employers.',
        'keywords' => 'AI job search, autonomous job applications, career OS, AI resume builder, interview preparation, S.C.O.U.T. ATS, StudAI Path',
        'og_title' => 'Your Career. On Autopilot. | StudAI Path',
        'og_description' => 'India\'s first Autonomous Career OS. AI finds jobs, applies for you, preps interviews, and negotiates salaries.',
    ],
    
    'job_search' => [
        'title' => 'AI Job Search — Find Your Perfect Role | StudAI Path',
        'description' => 'AI-powered job matching that goes beyond keywords. Find roles that fit your skills, goals, and culture preferences. Apply in one click.',
        'keywords' => 'AI job search, job matching, find jobs, career opportunities, remote jobs India',
    ],
    
    'autonomous_agent' => [
        'title' => 'Autonomous Job Application Agent — Apply While You Sleep | StudAI Path',
        'description' => 'Set your preferences and let AI apply to 100+ matching jobs daily. The first truly autonomous job application system.',
        'keywords' => 'autonomous job applications, AI agent, auto apply jobs, job automation',
    ],
    
    'resume_builder' => [
        'title' => 'AI Resume Builder — ATS-Optimized Resumes in 5 Minutes | StudAI Path',
        'description' => 'Create professional, ATS-optimized resumes with AI. 50+ templates, instant optimization, and guaranteed callbacks.',
        'keywords' => 'AI resume builder, ATS resume, resume templates, CV maker',
    ],
    
    'interview_ai' => [
        'title' => 'AI Interview Practice — Mock Interviews with Real-Time Feedback | StudAI Path',
        'description' => 'Practice interviews with AI. Get instant feedback on answers, body language, and delivery. Walk in confident.',
        'keywords' => 'AI interview practice, mock interviews, interview preparation, interview coaching',
    ],
    
    'employer_scout' => [
        'title' => 'S.C.O.U.T. AI — Bias-Free Hiring & ATS for Modern Companies | StudAI Path',
        'description' => 'S.C.O.U.T. AI removes hiring bias, automates screening, and helps you find top talent 60% faster. The future of fair hiring.',
        'keywords' => 'ATS software, applicant tracking system, bias-free hiring, AI recruiting, S.C.O.U.T.',
    ],
    
    'marketplace' => [
        'title' => 'Freelancer Marketplace — Find Projects & Talent | StudAI Path',
        'description' => 'Connect with verified freelancers or find curated projects. Secure escrow payments. AI-matched talent.',
        'keywords' => 'freelancer marketplace, hire freelancers, freelance jobs, gig economy',
    ],
    
    'pricing' => [
        'title' => 'Pricing — Simple Plans for Job Seekers & Employers | StudAI Path',
        'description' => 'Start free. Upgrade as you grow. Transparent pricing for job seekers and employers. No hidden fees.',
        'keywords' => 'StudAI Path pricing, career OS pricing, ATS pricing',
    ],
    
    'keyword_clusters' => [
        'primary' => ['AI job search', 'career OS', 'autonomous career', 'StudAI Path'],
        'job_seekers' => ['AI job applications', 'auto apply jobs', 'AI resume builder', 'interview AI'],
        'employers' => ['ATS software India', 'bias-free hiring', 'AI recruiting', 'S.C.O.U.T.'],
        'freelancers' => ['freelancer marketplace India', 'find projects', 'hire freelancers'],
        'features' => ['job matching AI', 'career automation', 'salary negotiation AI', 'market intelligence'],
    ],
];
@endphp


{{-- ============================================================================
SECTION 8: BRAND STORY PAGE
============================================================================ --}}

@php
$brand = [
    'hero' => [
        'title' => 'Building the Career OS for a Billion People.',
        'subtitle' => 'We\'re on a mission to make every career decision intelligent, autonomous, and fair.',
    ],
    
    'mission' => [
        'title' => 'Our Mission',
        'content' => 'To democratize career success by building AI that works for everyone — not just the privileged few.',
    ],
    
    'vision' => [
        'title' => 'Our Vision',
        'content' => 'A world where your career potential is realized regardless of your background, network, or pedigree. Where AI handles the mechanics of job hunting, and humans focus on doing meaningful work.',
    ],
    
    'why_we_exist' => [
        'title' => 'Why StudAI Path Exists',
        'paragraphs' => [
            'The job market is broken. Talented people spend months sending applications into the void. Employers wade through unqualified candidates because algorithms optimize for keywords, not potential. Hiring is slow, expensive, and riddled with unconscious bias.',
            'We believe there\'s a better way. AI is powerful enough now to match people with opportunities intelligently, apply autonomously, and remove bias from hiring. But no one had built a unified system to do it all.',
            'Until now.',
            'StudAI Path is India\'s first Autonomous Career OS — a single platform where your entire career journey is managed by intelligent agents that work 24/7 on your behalf.',
        ],
    ],
    
    'founder_story' => [
        'title' => 'A Note from Our Founder',
        'name' => 'Paul Jeevanesan A.',
        'role' => 'Founder & CEO',
        'content' => [
            'I grew up in Tamil Nadu, watching brilliant friends struggle to find opportunities because they didn\'t have the "right" connections or couldn\'t afford coaching. The playing field wasn\'t level.',
            'When I started building StudAI, I had one question: What if AI could be the great equalizer? What if we could build technology that gives everyone access to the same career intelligence that privileged candidates get?',
            'StudAI Path is my answer. It\'s a Career Operating System where AI agents find jobs, apply on your behalf, prepare you for interviews, and help you negotiate. It\'s hiring software that evaluates candidates on merit, not demographics.',
            'We\'re not just building a product. We\'re building the future of how India — and the world — thinks about careers.',
            'Welcome to the movement.',
        ],
        'signature' => 'Paul Jeevanesan A.',
    ],
    
    'movement' => [
        'title' => 'The Movement to Reshape India\'s Technological Narrative',
        'content' => 'India produces 1.5 million engineers every year. We have the talent. What we\'ve lacked is infrastructure that connects potential with opportunity at scale. StudAI Path is building that infrastructure — using AI to unlock human potential across the country.',
        'stats' => [
            ['value' => '1.5M', 'label' => 'Engineers graduate yearly'],
            ['value' => '60%', 'label' => 'Struggle to find relevant roles'],
            ['value' => '8 months', 'label' => 'Avg. time to first job'],
        ],
        'closing' => 'We\'re changing these numbers. One career at a time.',
    ],
    
    'values' => [
        [
            'title' => 'Autonomy First',
            'description' => 'We build AI that works for you, not the other way around.',
        ],
        [
            'title' => 'Radical Fairness',
            'description' => 'We actively remove bias from every algorithm we build.',
        ],
        [
            'title' => 'User Obsession',
            'description' => 'Every feature exists because it genuinely helps careers.',
        ],
        [
            'title' => 'Transparent AI',
            'description' => 'We explain what our AI does and why. No black boxes.',
        ],
    ],
];
@endphp


{{-- ============================================================================
SECTION 9: TALENT MARKETPLACE PAGES
============================================================================ --}}

@php
$marketplace = [
    'freelancer_profile' => [
        'sections' => [
            'hero' => [
                'title' => 'Showcase your expertise.',
                'subtitle' => 'Your profile is your portfolio. Make every section count.',
            ],
            'profile_sections' => [
                [
                    'title' => 'Professional Headline',
                    'placeholder' => 'e.g., Senior Full-Stack Developer | React & Node.js Expert',
                    'tip' => 'Be specific. Clients search by skills and specialties.',
                ],
                [
                    'title' => 'Overview',
                    'placeholder' => 'Tell clients about your experience, skills, and what makes you unique...',
                    'tip' => 'First 2 lines appear in search. Lead with your strongest value prop.',
                ],
                [
                    'title' => 'Skills',
                    'tip' => 'Add up to 15 skills. Skills with assessments rank higher.',
                ],
                [
                    'title' => 'Portfolio',
                    'tip' => 'Add projects that showcase your best work. Include results.',
                ],
                [
                    'title' => 'Experience',
                    'tip' => 'Relevant experience helps clients trust your expertise.',
                ],
                [
                    'title' => 'Rates',
                    'tip' => 'Set hourly and project-based rates. Research market rates first.',
                ],
            ],
        ],
    ],
    
    'project_posting' => [
        'hero' => [
            'title' => 'Find the perfect freelancer.',
            'subtitle' => 'Describe your project and get proposals from qualified professionals.',
        ],
        'form_sections' => [
            [
                'title' => 'Project Title',
                'placeholder' => 'e.g., Build a responsive e-commerce website',
                'tip' => 'Clear, specific titles attract better proposals.',
            ],
            [
                'title' => 'Description',
                'placeholder' => 'Describe your project goals, deliverables, and requirements...',
                'tip' => 'The more detail, the more accurate the proposals.',
            ],
            [
                'title' => 'Skills Required',
                'placeholder' => 'e.g., React, Node.js, PostgreSQL',
            ],
            [
                'title' => 'Budget',
                'options' => ['Fixed Price', 'Hourly'],
                'tip' => 'Fair budgets attract top talent.',
            ],
            [
                'title' => 'Timeline',
                'placeholder' => 'e.g., 4 weeks',
            ],
        ],
        'cta' => 'Post Project',
    ],
    
    'escrow' => [
        'hero' => [
            'title' => 'Payments you can trust.',
            'subtitle' => 'Escrow protection on every transaction. Pay only for approved work.',
        ],
        'how_it_works' => [
            [
                'step' => '01',
                'title' => 'Fund Milestone',
                'description' => 'Deposit funds when you approve a milestone. Money is held securely.',
            ],
            [
                'step' => '02',
                'title' => 'Work Delivered',
                'description' => 'Freelancer completes the milestone and submits for review.',
            ],
            [
                'step' => '03',
                'title' => 'Approve & Release',
                'description' => 'Review the work. Approve to release payment. Simple.',
            ],
        ],
        'guarantees' => [
            'Funds held in secure escrow until you approve',
            'Full dispute resolution if issues arise',
            'Automatic release if no response in 14 days',
            '100% payment protection for freelancers',
        ],
    ],
    
    'reviews' => [
        'hero' => [
            'title' => 'Reputation is everything.',
            'subtitle' => 'Verified reviews from real projects. Build trust that lasts.',
        ],
        'for_freelancers' => [
            'title' => 'Build Your Reputation',
            'points' => [
                'Verified reviews from completed projects',
                'Clients can only review after payment',
                'Response rate and delivery stats shown',
                'Top Rated badge for consistent excellence',
            ],
        ],
        'for_clients' => [
            'title' => 'Hire with Confidence',
            'points' => [
                'Read detailed reviews from past clients',
                'See project completion rates',
                'Verify skills through assessments',
                'Contact previous clients for references',
            ],
        ],
        'review_prompts' => [
            'quality' => 'How would you rate the quality of work?',
            'communication' => 'How responsive was communication?',
            'timeline' => 'Was the project delivered on time?',
            'recommend' => 'Would you recommend this freelancer?',
            'feedback' => 'Share details to help others make decisions...',
        ],
    ],
];
@endphp


{{-- ============================================================================
SECTION 10: UI TEXT FOR MOBILE APP (PWA)
============================================================================ --}}

@php
$pwa = [
    'navigation' => [
        'home' => 'Home',
        'jobs' => 'Jobs',
        'agent' => 'Agent',
        'applications' => 'Applications',
        'profile' => 'Profile',
        'more' => 'More',
    ],
    
    'bottom_bar' => [
        'home' => 'Home',
        'search' => 'Search',
        'agent' => 'Agent',
        'inbox' => 'Inbox',
        'me' => 'Me',
    ],
    
    'tooltips' => [
        'quick_apply' => 'Apply instantly with your profile',
        'save_job' => 'Save to review later',
        'ai_match' => 'How well this job matches you',
        'agent_active' => 'Agent is applying to jobs',
        'new_message' => 'You have unread messages',
    ],
    
    'quick_prompts' => [
        'morning' => 'Good morning! Your agent found 12 new matches.',
        'interview_prep' => 'Interview tomorrow? Start practicing now.',
        'application_update' => 'New update on your application.',
        'weekly_summary' => 'Your weekly career stats are ready.',
    ],
    
    'snackbars' => [
        'saved' => 'Job saved',
        'applied' => 'Application sent',
        'agent_on' => 'Agent activated',
        'agent_off' => 'Agent paused',
        'offline' => 'You\'re offline. Changes will sync.',
        'synced' => 'All changes synced',
    ],
    
    'pull_to_refresh' => 'Pull to refresh',
    'loading' => 'Loading...',
    'no_internet' => 'No internet connection',
    'retry' => 'Tap to retry',
];
@endphp
