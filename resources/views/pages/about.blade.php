@extends('layouts.app')

@section('title', 'About StudAI Career | Mission, Team & Vision For The Future Of Work')

@section('meta')
<meta name="description" content="Meet the people behind StudAI Career, explore our milestones, and learn how we are building the most intelligent career OS for emerging markets.">
<meta property="og:title" content="About StudAI Career">
<meta property="og:description" content="StudAI Career is on a mission to unlock career mobility with AI-powered workflows and inclusive hiring practices.">
<link rel="canonical" href="{{ route('about') }}">
@endsection

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white">
	<div class="absolute inset-0 opacity-30">
		<div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-blue-500/30 blur-3xl"></div>
		<div class="absolute top-20 right-0 h-[28rem] w-[28rem] rounded-full bg-purple-500/20 blur-3xl"></div>
		<div class="absolute bottom-0 left-1/2 h-[24rem] w-[24rem] -translate-x-1/2 rounded-full bg-blue-500/20 blur-3xl"></div>
	</div>
	<div class="relative mx-auto max-w-6xl px-6 py-24 lg:py-32">
		<div class="grid gap-12 lg:grid-cols-[1.4fr_1fr]">
			<div class="space-y-6">
				<span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-semibold uppercase tracking-[0.35em]">Our Story</span>
				<h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl">Building the career operating system for ambitious talent.</h1>
				<p class="text-lg text-slate-200">
					StudAI Career exists to eliminate friction between job seekers and employers. We combine applied AI, human-centered workflows, and local market intelligence to help millions access meaningful work and empower teams to hire with confidence.
				</p>
				<div class="flex flex-col gap-4 sm:flex-row sm:items-center">
					<a href="{{ route('pricing') }}" class="group inline-flex items-center rounded-xl bg-gradient-to-r from-[#1A73E8] via-blue-500 to-blue-400 px-8 py-3 text-lg font-semibold text-white shadow-2xl transition-all duration-300 hover:shadow-blue-500/40">
						Explore Plans
						<svg class="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
						</svg>
					</a>
					<a href="{{ route('contact') }}" class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 px-8 py-3 text-lg font-semibold text-white transition-all duration-300 hover:bg-white/20">
						Connect With Us
					</a>
				</div>
			</div>
			<div class="rounded-3xl border border-blue-500/30 bg-slate-900/70 p-8 shadow-2xl shadow-blue-500/20">
				<h2 class="text-2xl font-semibold text-white">Impact Snapshot</h2>
				<dl class="mt-6 grid gap-6 sm:grid-cols-2">
					@foreach ([
						['label' => 'Job seekers supported', 'value' => '210K+'],
						['label' => 'Employers onboarded', 'value' => '12,800'],
						['label' => 'Countries covered', 'value' => '18'],
						['label' => 'AI prompts processed monthly', 'value' => '4.5M'],
					] as $stat)
						<div>
							<dd class="text-3xl font-bold text-white">{{ $stat['value'] }}</dd>
							<dt class="mt-1 text-xs uppercase tracking-widest text-slate-400">{{ $stat['label'] }}</dt>
						</div>
					@endforeach
				</dl>
			</div>
		</div>
	</div>
</section>

<section class="bg-slate-950 py-20">
	<div class="mx-auto max-w-7xl px-6">
		<div class="grid gap-12 lg:grid-cols-[1.1fr_1fr]">
			<div>
				<h2 class="text-3xl font-bold text-white sm:text-4xl">Our Mission</h2>
				<p class="mt-4 text-lg text-slate-300">Unlock sustainable career mobility by pairing AI guidance with culturally-aware coaching for talent and hiring teams across fast-growing markets.</p>
				<div class="mt-8 space-y-6">
					@foreach ([
						['title' => 'Equity', 'body' => 'Level the playing field for candidates who have traditionally been overlooked by legacy recruitment systems.'],
						['title' => 'Velocity', 'body' => 'Compress the time from application to offer for both job seekers and employers without losing the human touch.'],
						['title' => 'Clarity', 'body' => 'Provide transparent insights at every stage, enabling smarter decisions on both sides of the hiring process.'],
					] as $principle)
						<div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
							<h3 class="text-xl font-semibold text-white">{{ $principle['title'] }}</h3>
							<p class="mt-2 text-slate-300">{{ $principle['body'] }}</p>
						</div>
					@endforeach
				</div>
			</div>
			<div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8">
				<h3 class="text-2xl font-semibold text-white">Guiding Beliefs</h3>
				<ul class="mt-4 space-y-4 text-slate-300">
					<li>AI should augment human potential, not replace it. We design collaborative workflows, not black boxes.</li>
					<li>Hiring must be inclusive by default. We bake bias checks and fairness metrics into every release.</li>
					<li>Career mobility is a continuous journey. Our platform evolves with new skills, industries, and work models.</li>
				</ul>
				<div class="mt-8 rounded-2xl border border-blue-500/30 bg-blue-500/10 p-6 text-blue-200">
					“StudAI Career helped us turn a 90-day hiring cycle into a 28-day sprint, without sacrificing candidate experience.”
					<div class="mt-4 text-sm font-semibold">— Kavya Menon, VP Talent, Verve Labs</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="bg-slate-950 py-20">
	<div class="mx-auto max-w-7xl px-6">
		<h2 class="text-center text-3xl font-bold text-white sm:text-4xl">Leadership & Advisors</h2>
		<p class="mx-auto mt-4 max-w-3xl text-center text-lg text-slate-300">We are builders, recruiters, and data scientists who have shipped hiring systems at scale across India, Southeast Asia, and Europe.</p>
		<div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
			@foreach ([
				['name' => 'Rohan Gupta', 'role' => 'Co-founder & CEO', 'bio' => 'Product leader with 12+ years at Microsoft and UiPath, focused on applied AI in enterprise workflows.'],
				['name' => 'Ashna Verma', 'role' => 'Co-founder & COO', 'bio' => 'Scaled recruitment marketplaces in India to 5M+ users; passionate about inclusive talent pipelines.'],
				['name' => 'Rahul Bhatia', 'role' => 'CTO', 'bio' => 'Former head of ML platform at a unicorn edtech. Leads our AI, data, and infrastructure teams.'],
				['name' => 'Ananya Iyer', 'role' => 'VP Growth', 'bio' => 'Built B2B go-to-market playbooks at Freshworks and Razorpay across APAC & EU.'],
				['name' => 'Dr. Megha Kulkarni', 'role' => 'Chief Scientist', 'bio' => 'PhD in Computational Linguistics. Designs fairness and explainability frameworks.'],
				['name' => 'Jeremy Lau', 'role' => 'Advisor, Talent Strategy', 'bio' => 'Former CHRO at Grab and Lazada. Guides enterprise hiring transformations.'],
			] as $leader)
				<div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-xl shadow-blue-500/10">
					<div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/20 text-lg font-semibold text-blue-200">
						{{ collect(explode(' ', $leader['name']))->map(fn($part) => strtoupper(substr($part, 0, 1)))->implode('') }}
					</div>
					<h3 class="mt-4 text-xl font-semibold text-white">{{ $leader['name'] }}</h3>
					<p class="text-sm uppercase tracking-wide text-pink-300">{{ $leader['role'] }}</p>
					<p class="mt-3 text-slate-300">{{ $leader['bio'] }}</p>
				</div>
			@endforeach
		</div>
	</div>
</section>

<section class="bg-slate-950 py-20">
	<div class="mx-auto max-w-7xl px-6">
		<div class="grid gap-12 lg:grid-cols-[1fr_1.1fr]">
			<div>
				<h2 class="text-3xl font-bold text-white sm:text-4xl">Milestones & Momentum</h2>
				<p class="mt-4 text-lg text-slate-300">From our first AI resume experiment to a full-stack hiring platform, every milestone reflects feedback from job seekers and hiring teams.</p>
				<div class="mt-8 space-y-6">
					@foreach ([
						['year' => '2022', 'title' => 'Prototype Launch', 'body' => 'We piloted our resume enrichment engine with 200 career changers across India.'],
						['year' => '2023', 'title' => 'Marketplace Expansion', 'body' => 'Introduced the employer console, onboarding 1,000+ hiring partners and dual database architecture.'],
						['year' => '2024', 'title' => 'AI Coach & Agents', 'body' => 'Released autonomous job search agents, learning path recommendations, and AI negotiation strategist.'],
						['year' => '2025', 'title' => 'Global Scaling', 'body' => 'Expanded to GCC and Southeast Asia with localized salary intelligence, compliance, and support teams.'],
					] as $milestone)
						<div class="flex gap-4 rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
							<div class="text-2xl font-semibold text-pink-300">{{ $milestone['year'] }}</div>
							<div>
								<h3 class="text-xl font-semibold text-white">{{ $milestone['title'] }}</h3>
								<p class="mt-1 text-slate-300">{{ $milestone['body'] }}</p>
							</div>
						</div>
					@endforeach
				</div>
			</div>
			<div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8">
				<h3 class="text-2xl font-semibold text-white">Life At StudAI Career</h3>
				<p class="mt-2 text-slate-300">We are a distributed-first team with hubs in Bengaluru, Singapore, and Berlin. Culture is built around autonomy, experimentation, and inclusivity.</p>
				<ul class="mt-6 space-y-4 text-slate-300">
					<li><span class="font-semibold text-white">Remote-friendly:</span> Hybrid work with quarterly in-person build weeks.</li>
					<li><span class="font-semibold text-white">Learning budget:</span> ₹80,000 annual stipend for courses, certifications, and conferences.</li>
					<li><span class="font-semibold text-white">Wellbeing:</span> Comprehensive health coverage, mental wellness stipend, and recharge weeks.</li>
					<li><span class="font-semibold text-white">Open source:</span> We contribute AI fairness tooling and hiring analytics components back to the community.</li>
				</ul>
				<a href="https://www.linkedin.com/company/studai-career/jobs" target="_blank" rel="noopener" class="mt-8 inline-flex items-center rounded-xl bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
					See Open Roles
				</a>
			</div>
		</div>
	</div>
</section>

<section class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 py-24">
	<div class="mx-auto max-w-6xl px-6">
		<div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-10 text-center shadow-2xl shadow-blue-500/20">
			<h2 class="text-3xl font-bold text-white sm:text-4xl">Shape The Future Of Work With Us</h2>
			<p class="mt-4 text-lg text-slate-300">Collaborate with us as a customer, partner, or team member. Let’s co-create the next chapter of careers.</p>
			<div class="mt-6 flex flex-col items-center justify-center gap-4 sm:flex-row">
				<a href="{{ route('register') }}" class="group inline-flex items-center rounded-xl bg-gradient-to-r from-[#1A73E8] via-blue-500 to-blue-400 px-8 py-3 text-lg font-semibold text-white transition-all duration-300 hover:shadow-2xl hover:shadow-blue-500/40">
					Join The Platform
					<svg class="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
					</svg>
				</a>
				<a href="{{ route('contact') }}" class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 px-8 py-3 text-lg font-semibold text-white transition-all duration-300 hover:bg-white/20">
					Partner With Us
				</a>
			</div>
		</div>
	</div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
	'@context' => 'https://schema.org',
	'@type' => 'Organization',
	'name' => 'StudAI Career',
	'url' => url('/'),
	'logo' => asset('images/logo-dark.svg'),
	'foundingDate' => '2022',
	'founders' => [
		['@type' => 'Person', 'name' => 'Rohan Gupta'],
		['@type' => 'Person', 'name' => 'Ashna Verma'],
	],
	'sameAs' => [
		'https://www.linkedin.com/company/studai-career',
		'https://twitter.com/studai_career'
	],
	'contactPoint' => [
		'@type' => 'ContactPoint',
		'contactType' => 'customer support',
		'email' => 'support@studai.careers',
		'areaServed' => ['IN', 'SG', 'AE', 'DE'],
		'availableLanguage' => ['English', 'Hindi'],
	],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
