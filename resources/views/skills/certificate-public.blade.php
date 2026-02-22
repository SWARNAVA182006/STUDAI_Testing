@extends('layouts.app')

@section('title', 'Skill Certificate')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-yellow-50 via-white to-amber-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(!$assessment->is_shareable)
        {{-- Not Shareable State --}}
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Certificate Not Shareable</h1>
            <p class="text-gray-600 mb-6">This certificate has been marked as private by the certificate holder.</p>
            <a href="/" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                Go to Homepage
            </a>
        </div>
        @elseif($assessment->certificate_expires_at && $assessment->certificate_expires_at->isPast())
        {{-- Expired State --}}
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-orange-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Certificate Expired</h1>
            <p class="text-gray-600 mb-2">This certificate expired on {{ $assessment->certificate_expires_at->format('F j, Y') }}</p>
            <p class="text-sm text-gray-500 mb-6">The skill proficiency shown below may no longer be current.</p>
            
            {{-- Show expired certificate in grayscale --}}
            <div class="opacity-50 grayscale">
                @include('skills.partials.certificate-display', ['assessment' => $assessment])
            </div>
        </div>
        @else
        {{-- Valid Certificate --}}
        <div class="mb-6 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Skill Proficiency Certificate</h1>
            <p class="text-gray-600">Verified achievement from {{ config('app.name') }}</p>
        </div>

        {{-- Certificate Card --}}
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border-8 border-gradient certificate-print">
            <div class="bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 h-4"></div>
            
            <div class="p-12">
                {{-- Header --}}
                <div class="text-center mb-8">
                    <div class="inline-block p-4 bg-yellow-100 rounded-full mb-4">
                        <svg class="w-16 h-16 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h2 class="text-4xl font-serif font-bold text-gray-900 mb-2">Certificate of Achievement</h2>
                    <p class="text-gray-600">This certifies that</p>
                </div>

                {{-- Recipient Name --}}
                <div class="text-center mb-8">
                    <h3 class="text-5xl font-serif font-bold text-gray-900 mb-1">{{ $assessment->user->name }}</h3>
                    <p class="text-gray-600">has successfully demonstrated proficiency in</p>
                </div>

                {{-- Skill Name --}}
                <div class="text-center mb-8">
                    <div class="inline-block px-8 py-4 bg-gradient-to-r from-yellow-100 to-amber-100 rounded-lg border-2 border-yellow-300">
                        <h4 class="text-3xl font-serif font-bold text-gray-900">{{ $assessment->skill_name }}</h4>
                    </div>
                </div>

                {{-- Achievement Details --}}
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600 mb-1">Proficiency Level</p>
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-bold
                            @if($assessment->proficiency_awarded === 'expert') bg-purple-100 text-purple-800 border-2 border-purple-300
                            @elseif($assessment->proficiency_awarded === 'advanced') bg-blue-100 text-blue-800 border-2 border-blue-300
                            @elseif($assessment->proficiency_awarded === 'intermediate') bg-green-100 text-green-800 border-2 border-green-300
                            @else bg-gray-100 text-gray-800 border-2 border-gray-300
                            @endif">
                            {{ ucfirst($assessment->proficiency_awarded ?? 'Proficient') }}
                        </span>
                    </div>

                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600 mb-1">Assessment Score</p>
                        <div class="inline-flex items-center">
                            <span class="text-4xl font-bold text-gray-900">{{ $assessment->score }}%</span>
                            <span class="ml-2 text-2xl font-bold
                                @if($assessment->grade === 'A') text-green-600
                                @elseif($assessment->grade === 'B') text-blue-600
                                @elseif($assessment->grade === 'C') text-yellow-600
                                @else text-gray-600
                                @endif">
                                ({{ $assessment->grade }})
                            </span>
                        </div>
                    </div>

                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600 mb-1">Assessment Type</p>
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border-2 border-blue-300">
                            {{ ucfirst($assessment->assessment_type) }}
                        </span>
                    </div>
                </div>

                {{-- Date & Signature --}}
                <div class="flex items-end justify-between pt-8 border-t-2 border-gray-200">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Date Issued</p>
                        <p class="text-lg font-bold text-gray-900">{{ $assessment->completed_at->format('F j, Y') }}</p>
                        @if($assessment->certificate_expires_at)
                        <p class="text-xs text-gray-500 mt-1">Valid until {{ $assessment->certificate_expires_at->format('F j, Y') }}</p>
                        @endif
                    </div>

                    <div class="text-right">
                        <div class="mb-2">
                            <img src="{{ asset('images/signature.png') }}" alt="Signature" class="h-12" onerror="this.style.display='none'">
                        </div>
                        <p class="text-sm font-medium text-gray-900 border-t-2 border-gray-400 pt-1">{{ config('app.name') }}</p>
                        <p class="text-xs text-gray-600">Skill Assessment Platform</p>
                    </div>
                </div>

                {{-- Certificate Hash --}}
                <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                    <p class="text-xs text-gray-500 mb-2">Certificate ID: {{ $assessment->certificate_hash }}</p>
                    <p class="text-xs text-gray-400">Verify this certificate at {{ url()->current() }}</p>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 h-4"></div>
        </div>

        {{-- Verification Section --}}
        <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">🔐 Certificate Verification</h3>
                    <p class="text-sm text-gray-600 mb-4">This certificate is cryptographically verified and can be authenticated using the certificate ID above.</p>
                    
                    <div class="flex items-center space-x-2 text-sm">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium text-green-800">Valid Certificate</span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm mt-1">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium text-green-800">Issued by {{ config('app.name') }}</span>
                    </div>
                    @if(!$assessment->certificate_expires_at || $assessment->certificate_expires_at->isFuture())
                    <div class="flex items-center space-x-2 text-sm mt-1">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium text-green-800">Currently Valid</span>
                    </div>
                    @endif
                </div>

                {{-- QR Code --}}
                <div class="ml-6 text-center">
                    <div class="w-32 h-32 bg-gray-100 rounded-lg flex items-center justify-center mb-2">
                        <div id="qrcode"></div>
                    </div>
                    <p class="text-xs text-gray-500">Scan to verify</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-8 flex items-center justify-center space-x-4">
            <button onclick="window.print()" class="inline-flex items-center px-6 py-3 bg-gray-800 text-white rounded-lg font-medium hover:bg-gray-900 transition-colors shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Download PDF
            </button>

            <button onclick="shareLinkedIn()" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.338 16.338H13.67V12.16c0-.995-.017-2.277-1.387-2.277-1.39 0-1.601 1.086-1.601 2.207v4.248H8.014v-8.59h2.559v1.174h.037c.356-.675 1.227-1.387 2.526-1.387 2.703 0 3.203 1.778 3.203 4.092v4.711zM5.005 6.575a1.548 1.548 0 11-.003-3.096 1.548 1.548 0 01.003 3.096zm-1.337 9.763H6.34v-8.59H3.667v8.59zM17.668 1H2.328C1.595 1 1 1.581 1 2.298v15.403C1 18.418 1.595 19 2.328 19h15.34c.734 0 1.332-.582 1.332-1.299V2.298C19 1.581 18.402 1 17.668 1z" clip-rule="evenodd"/>
                </svg>
                Share on LinkedIn
            </button>

            <button onclick="shareTwitter()" class="inline-flex items-center px-6 py-3 bg-sky-500 text-white rounded-lg font-medium hover:bg-sky-600 transition-colors shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                </svg>
                Share on Twitter
            </button>

            <button onclick="copyLink()" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Copy Link
            </button>
        </div>
        @endif
    </div>
</div>

{{-- QR Code Library --}}
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
// Generate QR Code
@if($assessment->is_shareable && (!$assessment->certificate_expires_at || $assessment->certificate_expires_at->isFuture()))
const qrcode = new QRCode(document.getElementById("qrcode"), {
    text: "{{ url()->current() }}",
    width: 128,
    height: 128,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
@endif

function shareLinkedIn() {
    const url = encodeURIComponent("{{ url()->current() }}");
    const text = encodeURIComponent("I just earned a {{ $assessment->proficiency_awarded ?? 'proficiency' }} certificate in {{ $assessment->skill_name }} with a score of {{ $assessment->score }}%! #SkillDevelopment #ProfessionalGrowth");
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank');
}

function shareTwitter() {
    const url = encodeURIComponent("{{ url()->current() }}");
    const text = encodeURIComponent("Just earned a {{ $assessment->proficiency_awarded ?? 'proficiency' }} certificate in {{ $assessment->skill_name }}! Score: {{ $assessment->score }}% 🎓");
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
}

function copyLink() {
    const url = "{{ url()->current() }}";
    navigator.clipboard.writeText(url).then(() => {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-5 h-5 mr-2 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Copied!';
        setTimeout(() => {
            button.innerHTML = originalText;
        }, 2000);
    });
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .certificate-print, .certificate-print * {
        visibility: visible;
    }
    .certificate-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}

.border-gradient {
    border-image: linear-gradient(to right, #fbbf24, #f59e0b, #f97316) 1;
}

.grayscale {
    filter: grayscale(100%);
}
</style>
@endsection
