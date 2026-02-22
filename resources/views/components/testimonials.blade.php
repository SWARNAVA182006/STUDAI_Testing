@props([
    'title' => 'What Our Users Say',
    'subtitle' => 'Join thousands of successful job seekers',
    'testimonials' => []
])

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                {{ $title }}
            </h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                {{ $subtitle }}
            </p>
        </div>

        {{-- Testimonials Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($testimonials as $index => $testimonial)
            <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition border border-gray-100"
                 data-aos="fade-up"
                 data-aos-delay="{{ $index * 100 }}">
                {{-- Rating Stars --}}
                <div class="flex items-center mb-4">
                    @for($i = 0; $i < ($testimonial['rating'] ?? 5); $i++)
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>

                {{-- Testimonial Text --}}
                <blockquote class="text-gray-700 mb-6 leading-relaxed">
                    "{{ $testimonial['content'] ?? 'Great platform for job search!' }}"
                </blockquote>

                {{-- Author Info --}}
                <div class="flex items-center">
                    @if(isset($testimonial['avatar']))
                    <img src="{{ $testimonial['avatar'] }}" 
                         alt="{{ $testimonial['name'] ?? 'User' }}"
                         class="w-12 h-12 rounded-full object-cover mr-4"
                         loading="lazy">
                    @else
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-400 to-purple-400 flex items-center justify-center text-white font-bold text-lg mr-4">
                        {{ substr($testimonial['name'] ?? 'U', 0, 1) }}
                    </div>
                    @endif

                    <div>
                        <div class="font-semibold text-gray-900">
                            {{ $testimonial['name'] ?? 'Anonymous User' }}
                        </div>
                        <div class="text-sm text-gray-600">
                            {{ $testimonial['position'] ?? 'Job Seeker' }}
                            @if(isset($testimonial['company']))
                            <span class="text-gray-400">at</span> {{ $testimonial['company'] }}
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Optional verified badge --}}
                @if($testimonial['verified'] ?? false)
                <div class="mt-4 flex items-center text-sm text-green-600">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Verified User
                </div>
                @endif
            </div>
            @empty
            {{-- Default Testimonials if none provided --}}
            <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition border border-gray-100" data-aos="fade-up">
                <div class="flex items-center mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <blockquote class="text-gray-700 mb-6 leading-relaxed">
                    "StudAI Career transformed my job search! The AI-powered matching found me perfect opportunities I wouldn't have discovered otherwise. Landed my dream job in just 3 weeks!"
                </blockquote>
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-400 to-purple-400 flex items-center justify-center text-white font-bold text-lg mr-4">
                        P
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">Priya Sharma</div>
                        <div class="text-sm text-gray-600">Software Engineer <span class="text-gray-400">at</span> Tech Corp</div>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-green-600">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Verified User
                </div>
            </div>

            <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition border border-gray-100" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <blockquote class="text-gray-700 mb-6 leading-relaxed">
                    "The resume optimization and interview prep tools are game-changers. My interview success rate went from 20% to 80%. Highly recommended for every job seeker!"
                </blockquote>
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-400 flex items-center justify-center text-white font-bold text-lg mr-4">
                        R
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">Rahul Patel</div>
                        <div class="text-sm text-gray-600">Product Manager <span class="text-gray-400">at</span> Startup Inc</div>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-green-600">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Verified User
                </div>
            </div>

            <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition border border-gray-100" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <blockquote class="text-gray-700 mb-6 leading-relaxed">
                    "As a recruiter, I've never seen candidates so well-prepared. StudAI Career users always stand out with optimized resumes and impressive interview skills."
                </blockquote>
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-teal-400 flex items-center justify-center text-white font-bold text-lg mr-4">
                        A
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">Anjali Mehta</div>
                        <div class="text-sm text-gray-600">HR Manager <span class="text-gray-400">at</span> Global Solutions</div>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-green-600">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Verified User
                </div>
            </div>
            @endforelse
        </div>

        {{-- Additional Trust Signals --}}
        <div class="mt-16 text-center" data-aos="fade-up">
            <p class="text-gray-600 mb-6">Trusted by professionals at leading companies</p>
            <div class="flex flex-wrap justify-center items-center gap-8 opacity-60">
                {{-- Company logos placeholder --}}
                <div class="text-gray-400 font-semibold text-lg">Google</div>
                <div class="text-gray-400 font-semibold text-lg">Microsoft</div>
                <div class="text-gray-400 font-semibold text-lg">Amazon</div>
                <div class="text-gray-400 font-semibold text-lg">Meta</div>
                <div class="text-gray-400 font-semibold text-lg">Apple</div>
            </div>
        </div>
    </div>
</section>
