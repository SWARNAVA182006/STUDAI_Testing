@extends('layouts.app')

@section('title', 'Blog & Resources - StudAI Career | Career Insights, Industry Trends & Guides')

@section('meta')
<meta name="description" content="Discover career insights, industry trends, and comprehensive guides on AI-powered job search, hiring best practices, and professional development from StudAI Career experts.">
<meta name="keywords" content="career blog, job search tips, hiring trends, AI recruitment, career development, professional growth, industry insights">
<meta property="og:title" content="Blog & Resources - StudAI Career">
<meta property="og:description" content="Expert insights on careers, AI recruitment, and professional development. Stay ahead with the latest trends and actionable advice.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('blog') }}">
<link rel="canonical" href="{{ route('blog') }}">
@endsection

@section('content')
<!-- Hero Section -->
<section class="relative py-24 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 overflow-hidden">
    <!-- Background Animation -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 -left-4 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 -right-4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center text-white space-y-8">
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/20">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span class="text-sm font-medium">Expert Insights & Resources</span>
            </div>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold leading-tight">
                Career Insights &
                <span class="bg-gradient-to-r from-[#1A73E8] via-blue-400 to-cyan-400 bg-clip-text text-transparent">
                    Expert Guidance
                </span>
            </h1>

            <p class="text-xl md:text-2xl text-gray-200 leading-relaxed max-w-4xl mx-auto">
                Stay ahead of the curve with actionable career advice, industry trends, and comprehensive guides 
                to accelerate your professional growth in the AI-powered future of work.
            </p>

            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto">
                <div class="relative">
                    <input type="text" placeholder="Search articles, guides, and resources..." class="w-full px-6 py-4 pr-12 text-lg bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-gray-300 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-16 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center gap-4">
            @foreach ([
                ['name' => 'All Articles', 'count' => 124, 'active' => true],
                ['name' => 'Career Development', 'count' => 32, 'active' => false],
                ['name' => 'AI & Technology', 'count' => 28, 'active' => false],
                ['name' => 'Job Search Tips', 'count' => 24, 'active' => false],
                ['name' => 'Industry Trends', 'count' => 19, 'active' => false],
                ['name' => 'Hiring & Recruitment', 'count' => 21, 'active' => false]
            ] as $category)
                <button class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all duration-300 {{ $category['active'] ? 'bg-gradient-to-r from-[#1A73E8] to-blue-600 text-white shadow-lg' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50 hover:text-white' }}">
                    {{ $category['name'] }}
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">{{ $category['count'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</section>

<!-- Featured Article -->
<section class="py-16 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Featured Article</h2>
            <p class="text-xl text-gray-300">Our most comprehensive guide to succeeding in the AI-powered job market</p>
        </div>

        <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 overflow-hidden hover:border-pink-500/30 transition-all duration-300">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
                <div class="aspect-video lg:aspect-square bg-gradient-to-br from-pink-500/20 to-purple-500/20 flex items-center justify-center">
                    <div class="text-center text-white">
                        <svg class="w-24 h-24 mx-auto mb-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <p class="text-sm text-gray-300">Featured Image</p>
                    </div>
                </div>
                <div class="p-8 lg:p-12 flex flex-col justify-center">
                    <div class="flex items-center gap-4 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-pink-500/20 text-pink-300 border border-pink-500/30">
                            Career Development
                        </span>
                        <span class="text-sm text-gray-400">Dec 15, 2024 • 12 min read</span>
                    </div>
                    <h3 class="text-3xl font-bold text-white mb-4 leading-tight">
                        The Complete Guide to AI-Powered Job Search: From Profile Optimization to Offer Negotiation
                    </h3>
                    <p class="text-lg text-gray-300 mb-6 leading-relaxed">
                        Discover how artificial intelligence is transforming the job search landscape and learn 
                        actionable strategies to leverage AI tools, optimize your digital presence, and 
                        accelerate your career progression in 2025.
                    </p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-[#1A73E8] to-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                AS
                            </div>
                            <div>
                                <p class="text-white font-semibold">Arjun Sharma</p>
                                <p class="text-sm text-gray-400">Lead Career Strategist</p>
                            </div>
                        </div>
                        <a href="#" class="inline-flex items-center gap-2 text-pink-400 hover:text-pink-300 font-semibold transition-colors">
                            Read Article
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Articles Grid -->
<section class="py-16 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Latest Articles</h2>
            <p class="text-xl text-gray-300">Stay updated with our latest insights and expert advice</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ([
                [
                    'category' => 'AI & Technology',
                    'title' => '5 AI Tools Every Job Seeker Should Know in 2025',
                    'excerpt' => 'Discover the most powerful AI tools that can transform your job search, from resume optimization to interview preparation.',
                    'author' => 'Priya Mehta',
                    'date' => 'Dec 12, 2024',
                    'readTime' => '7 min read',
                    'color' => 'blue'
                ],
                [
                    'category' => 'Job Search Tips',
                    'title' => 'How to Write ATS-Friendly Resumes That Get Results',
                    'excerpt' => 'Learn the secrets of creating resumes that pass applicant tracking systems and impress human recruiters.',
                    'author' => 'Rajesh Kumar',
                    'date' => 'Dec 10, 2024',
                    'readTime' => '5 min read',
                    'color' => 'green'
                ],
                [
                    'category' => 'Industry Trends',
                    'title' => 'Remote Work Revolution: Skills That Matter Most',
                    'excerpt' => 'Explore the top skills and competencies that are driving success in the remote-first job market.',
                    'author' => 'Sarah Chen',
                    'date' => 'Dec 8, 2024',
                    'readTime' => '8 min read',
                    'color' => 'purple'
                ],
                [
                    'category' => 'Career Development',
                    'title' => 'Salary Negotiation Strategies for the Indian Market',
                    'excerpt' => 'Master the art of salary negotiation with proven strategies tailored for Indian professionals.',
                    'author' => 'Vikram Patel',
                    'date' => 'Dec 5, 2024',
                    'readTime' => '10 min read',
                    'color' => 'pink'
                ],
                [
                    'category' => 'Hiring & Recruitment',
                    'title' => 'The Future of Hiring: AI vs Human Judgment',
                    'excerpt' => 'Understand how AI is reshaping recruitment while maintaining the human element in hiring decisions.',
                    'author' => 'Lisa Thompson',
                    'date' => 'Dec 3, 2024',
                    'readTime' => '6 min read',
                    'color' => 'indigo'
                ],
                [
                    'category' => 'AI & Technology',
                    'title' => 'Building Your Personal Brand in the Digital Age',
                    'excerpt' => 'Create a compelling online presence that attracts opportunities and showcases your expertise.',
                    'author' => 'Ananya Gupta',
                    'date' => 'Dec 1, 2024',
                    'readTime' => '9 min read',
                    'color' => 'teal'
                ]
            ] as $article)
                <article class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 overflow-hidden hover:border-pink-500/30 transition-all duration-300 group">
                    <div class="aspect-video bg-gradient-to-br from-{{ $article['color'] }}-500/20 to-{{ $article['color'] }}-600/20 flex items-center justify-center">
                        <svg class="w-16 h-16 text-{{ $article['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-{{ $article['color'] }}-500/20 text-{{ $article['color'] }}-300 border border-{{ $article['color'] }}-500/30">
                                {{ $article['category'] }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $article['readTime'] }}</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-pink-400 transition-colors leading-tight">
                            {{ $article['title'] }}
                        </h3>
                        <p class="text-gray-300 mb-4 leading-relaxed">
                            {{ $article['excerpt'] }}
                        </p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-{{ $article['color'] }}-500 to-{{ $article['color'] }}-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                    {{ collect(explode(' ', $article['author']))->map(fn($part) => strtoupper(substr($part, 0, 1)))->implode('') }}
                                </div>
                                <div>
                                    <p class="text-sm text-white font-medium">{{ $article['author'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $article['date'] }}</p>
                                </div>
                            </div>
                            <a href="#" class="text-{{ $article['color'] }}-400 hover:text-{{ $article['color'] }}-300 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-12">
            <button class="inline-flex items-center gap-2 px-8 py-4 bg-slate-800/50 hover:bg-slate-700/50 text-white font-semibold rounded-xl border border-slate-700 hover:border-pink-500/30 transition-all duration-300">
                Load More Articles
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </button>
        </div>
    </div>
</section>

<!-- Resources Section -->
<section class="py-16 bg-gradient-to-b from-slate-950 to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Career Resources</h2>
            <p class="text-xl text-gray-300">Comprehensive guides and tools to accelerate your career growth</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach ([
                [
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'title' => 'Resume Templates',
                    'description' => 'ATS-optimized resume templates for different industries and experience levels.',
                    'count' => '25+ Templates',
                    'color' => 'pink'
                ],
                [
                    'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => 'Interview Guides',
                    'description' => 'Complete interview preparation guides with common questions and expert answers.',
                    'count' => '50+ Questions',
                    'color' => 'blue'
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
                    'title' => 'Salary Reports',
                    'description' => 'Up-to-date salary data and compensation insights across industries.',
                    'count' => '100+ Roles',
                    'color' => 'green'
                ],
                [
                    'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                    'title' => 'Skill Assessments',
                    'description' => 'Free assessments to identify your strengths and skill gaps.',
                    'count' => '20+ Skills',
                    'color' => 'purple'
                ]
            ] as $resource)
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 text-center hover:border-{{ $resource['color'] }}-500/30 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-{{ $resource['color'] }}-500/20 to-{{ $resource['color'] }}-600/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-{{ $resource['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $resource['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">{{ $resource['title'] }}</h3>
                    <p class="text-gray-300 mb-4 leading-relaxed">{{ $resource['description'] }}</p>
                    <p class="text-sm text-{{ $resource['color'] }}-400 font-semibold mb-6">{{ $resource['count'] }}</p>
                    <a href="#" class="inline-flex items-center gap-2 text-{{ $resource['color'] }}-400 hover:text-{{ $resource['color'] }}-300 font-semibold transition-colors">
                        Explore Resource
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Newsletter Signup -->
<section class="py-16 bg-slate-950">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-gradient-to-br from-pink-500/10 to-purple-500/10 rounded-3xl border border-pink-500/20 p-12 backdrop-blur-md">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Stay Ahead with Career Insights
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                Get weekly career tips, industry trends, and exclusive resources delivered to your inbox.
            </p>
            <form class="flex flex-col sm:flex-row gap-4 max-w-lg mx-auto">
                <input type="email" placeholder="Enter your email address" class="flex-1 px-6 py-4 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit" class="px-8 py-4 bg-gradient-to-r from-[#1A73E8] to-blue-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300">
                    Subscribe
                </button>
            </form>
            <p class="text-sm text-gray-400 mt-4">No spam, unsubscribe anytime. Join 50,000+ professionals.</p>
        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => 'StudAI Career Blog',
    'description' => 'Career insights, industry trends, and expert guidance for professional growth',
    'url' => route('blog'),
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'StudAI Career',
        'url' => url('/'),
        'logo' => asset('images/logo-dark.svg')
    ],
    'blogPost' => [
        [
            '@type' => 'BlogPosting',
            'headline' => 'The Complete Guide to AI-Powered Job Search',
            'description' => 'Discover how artificial intelligence is transforming the job search landscape',
            'author' => [
                '@type' => 'Person',
                'name' => 'Arjun Sharma'
            ],
            'datePublished' => '2024-12-15',
            'url' => '#'
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@push('styles')
<style>
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}
</style>
@endpush
