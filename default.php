<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-WFTMQNG6');</script>
    <!-- End Google Tag Manager -->
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="StudAI Career - AI-powered career platform helping job seekers and employers connect meaningfully. Join 210K+ professionals building their future.">
    <meta name="keywords" content="career platform, job search, AI recruitment, hiring, jobs, resume builder">
    <meta property="og:title" content="StudAI Career - Build Your Future with AI-Powered Career Intelligence">
    <meta property="og:description" content="Transform your career journey with intelligent job matching, AI-powered resume optimization, and personalized career guidance.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="/images/og-image.jpg">
    
    <title>StudAI Career - AI-Powered Career Platform | Job Search Made Intelligent</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        :root {
            --primary-color: #ec4899;
            --secondary-color: #10b981;
            --accent-blue: #3b82f6;
            --accent-yellow: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }

        .animated-gradient {
            background: linear-gradient(-45deg, #ec4899, #8b5cf6, #3b82f6, #10b981);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .glassmorphism {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .fade-in {
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .floating {
            animation: floating 6s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .shine {
            position: relative;
            overflow: hidden;
        }

        .shine::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .shine:hover::before {
            left: 100%;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WFTMQNG6"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Background Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-pink-500/20 rounded-full blur-3xl floating"></div>
        <div class="absolute top-1/3 -left-20 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl floating" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-20 right-1/4 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl floating" style="animation-delay: 4s;"></div>
    </div>

    <!-- Main Content -->
    <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-12">
        
        <!-- Logo and Branding -->
        <div class="text-center mb-12 fade-in">
            <div class="inline-flex items-center gap-3 mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-pink-500/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold">
                    <span class="text-white">StudAI</span>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-purple-400"> Career</span>
                </h1>
            </div>
            <p class="text-xl md:text-2xl text-gray-300 max-w-2xl mx-auto mb-4">
                AI-Powered Career Intelligence Platform
            </p>
            <p class="text-sm text-gray-400 max-w-xl mx-auto">
                Transforming careers with intelligent job matching, AI resume optimization, and personalized career guidance for 210K+ professionals
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12 w-full max-w-4xl">
            <div class="glassmorphism rounded-2xl p-6 text-center fade-in" style="animation-delay: 0.1s;">
                <div class="text-3xl md:text-4xl font-bold text-pink-400 mb-2">210K+</div>
                <div class="text-sm text-gray-400">Job Seekers</div>
            </div>
            <div class="glassmorphism rounded-2xl p-6 text-center fade-in" style="animation-delay: 0.2s;">
                <div class="text-3xl md:text-4xl font-bold text-purple-400 mb-2">12.8K</div>
                <div class="text-sm text-gray-400">Employers</div>
            </div>
            <div class="glassmorphism rounded-2xl p-6 text-center fade-in" style="animation-delay: 0.3s;">
                <div class="text-3xl md:text-4xl font-bold text-blue-400 mb-2">18</div>
                <div class="text-sm text-gray-400">Countries</div>
            </div>
            <div class="glassmorphism rounded-2xl p-6 text-center fade-in" style="animation-delay: 0.4s;">
                <div class="text-3xl md:text-4xl font-bold text-green-400 mb-2">4.5M</div>
                <div class="text-sm text-gray-400">AI Prompts/Month</div>
            </div>
        </div>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 mb-12 fade-in" style="animation-delay: 0.5s;">
            <a href="/public/" 
               class="group relative px-8 py-4 bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 text-white font-semibold rounded-xl shadow-2xl hover:shadow-pink-500/40 transition-all duration-300 shine overflow-hidden">
                <span class="relative z-10 flex items-center gap-2">
                    Launch Platform
                    <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </span>
            </a>
            <a href="/public/register" 
               class="px-8 py-4 glassmorphism text-white font-semibold rounded-xl hover:bg-white/10 transition-all duration-300">
                Create Account
            </a>
        </div>

        <!-- Feature Highlights -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl">
            <div class="glassmorphism rounded-2xl p-6 fade-in hover:bg-white/10 transition-all duration-300" style="animation-delay: 0.6s;">
                <div class="w-12 h-12 bg-pink-500/20 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">AI Job Matching</h3>
                <p class="text-sm text-gray-400">Intelligent algorithms match you with perfect opportunities based on skills, experience, and career goals</p>
            </div>

            <div class="glassmorphism rounded-2xl p-6 fade-in hover:bg-white/10 transition-all duration-300" style="animation-delay: 0.7s;">
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Resume Optimization</h3>
                <p class="text-sm text-gray-400">AI-powered resume reviews and ATS optimization to increase your chances of getting noticed</p>
            </div>

            <div class="glassmorphism rounded-2xl p-6 fade-in hover:bg-white/10 transition-all duration-300" style="animation-delay: 0.8s;">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Career Insights</h3>
                <p class="text-sm text-gray-400">Data-driven career guidance and market intelligence to help you make informed decisions</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-16 text-center text-sm text-gray-500 fade-in" style="animation-delay: 0.9s;">
            <p class="mb-2">© 2024 StudAI Career Private Limited. All rights reserved.</p>
            <div class="flex items-center justify-center gap-4">
                <a href="/public/privacy" class="hover:text-gray-300 transition-colors">Privacy Policy</a>
                <span>•</span>
                <a href="/public/terms" class="hover:text-gray-300 transition-colors">Terms of Service</a>
                <span>•</span>
                <a href="/public/contact" class="hover:text-gray-300 transition-colors">Contact</a>
            </div>
        </div>
    </div>

    <!-- Loading Overlay (for smooth transition) -->
    <div id="loading-overlay" class="fixed inset-0 bg-slate-950 z-50 flex items-center justify-center transition-opacity duration-500 opacity-0 pointer-events-none">
        <div class="text-center">
            <div class="inline-block w-16 h-16 border-4 border-pink-500 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-white text-lg">Loading platform...</p>
        </div>
    </div>

    <script>
        // Smooth page load animation
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s';
                document.body.style.opacity = '1';
            }, 100);
        });

        // Show loading overlay on navigation
        document.querySelectorAll('a[href^="/public/"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const overlay = document.getElementById('loading-overlay');
                overlay.style.pointerEvents = 'auto';
                overlay.style.opacity = '1';
            });
        });

        // Auto-redirect after 3 seconds if user doesn't click
        setTimeout(() => {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('stay')) {
                console.log('Auto-redirecting to platform...');
                // Uncomment to enable auto-redirect:
                // window.location.href = '/public/';
            }
        }, 10000); // 10 seconds
    </script>
</body>
</html>