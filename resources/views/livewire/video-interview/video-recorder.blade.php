<div class="min-h-screen bg-gray-900" x-data="videoRecorder()" x-init="init()">
    <!-- Top Bar -->
    <div class="bg-gray-800 border-b border-gray-700 px-4 py-3">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center">
                <h1 class="text-lg font-semibold text-white">{{ $session->title }}</h1>
                <span class="ml-3 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-500/20 text-indigo-300">
                    Question {{ $currentQuestionIndex + 1 }} of {{ $totalQuestions }}
                </span>
            </div>
            
            <!-- Progress Bar -->
            <div class="flex items-center gap-4">
                <div class="w-48 bg-gray-700 rounded-full h-2">
                    <div class="bg-indigo-500 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $progressPercentage }}%"></div>
                </div>
                <span class="text-sm text-gray-400">{{ $progressPercentage }}%</span>
            </div>
        </div>
    </div>

    @if($showInstructions)
        <!-- Instructions Screen -->
        <div class="max-w-3xl mx-auto py-12 px-4">
            <div class="bg-gray-800 rounded-xl p-8 text-center">
                <div class="w-16 h-16 bg-indigo-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-4">Ready to Begin?</h2>
                <p class="text-gray-400 mb-8 max-w-lg mx-auto">
                    You'll be asked {{ $totalQuestions }} interview questions. Take your time to prepare 
                    for each question before recording your response.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 text-left">
                    <div class="bg-gray-700/50 rounded-lg p-4">
                        <div class="text-indigo-400 font-semibold mb-1">Step 1</div>
                        <div class="text-sm text-gray-300">Read the question and prepare your answer</div>
                    </div>
                    <div class="bg-gray-700/50 rounded-lg p-4">
                        <div class="text-indigo-400 font-semibold mb-1">Step 2</div>
                        <div class="text-sm text-gray-300">Record your video response</div>
                    </div>
                    <div class="bg-gray-700/50 rounded-lg p-4">
                        <div class="text-indigo-400 font-semibold mb-1">Step 3</div>
                        <div class="text-sm text-gray-300">Review and submit or retake</div>
                    </div>
                </div>

                <!-- Camera Preview -->
                <div class="mb-8">
                    <div class="aspect-video max-w-md mx-auto bg-gray-900 rounded-lg overflow-hidden relative">
                        <video x-ref="previewVideo" autoplay muted playsinline 
                               class="w-full h-full object-cover"></video>
                        <div x-show="!cameraReady" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-400 text-sm">Accessing camera...</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Make sure your camera and microphone are working</p>
                </div>

                <button wire:click="startInterview" 
                        :disabled="!cameraReady"
                        :class="{ 'opacity-50 cursor-not-allowed': !cameraReady }"
                        class="px-8 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Start Interview
                </button>
            </div>
        </div>

    @elseif($status === 'completed')
        <!-- Completed Screen -->
        <div class="max-w-3xl mx-auto py-12 px-4">
            <div class="bg-gray-800 rounded-xl p-8 text-center">
                <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-4">Interview Complete!</h2>
                <p class="text-gray-400 mb-8">
                    Great job! Your responses are being analyzed. You'll receive detailed feedback shortly.
                </p>
                <div class="flex items-center justify-center gap-4">
                    <a href="{{ route('video-interview.results', $session) }}" 
                       class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        View Results
                    </a>
                    <a href="{{ route('video-interview.sessions') }}" 
                       class="px-6 py-3 bg-gray-700 text-white font-semibold rounded-lg hover:bg-gray-600 transition">
                        Back to Sessions
                    </a>
                </div>
            </div>
        </div>

    @else
        <!-- Recording Interface -->
        <div class="max-w-6xl mx-auto py-6 px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Video Area -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-800 rounded-xl overflow-hidden">
                        <!-- Video Container -->
                        <div class="aspect-video relative bg-gray-900">
                            <!-- Live Preview -->
                            <video x-ref="recordingVideo" 
                                   x-show="status !== 'reviewing'" 
                                   autoplay muted playsinline 
                                   class="w-full h-full object-cover"></video>
                            
                            <!-- Playback Preview -->
                            <video x-ref="playbackVideo" 
                                   x-show="status === 'reviewing'" 
                                   controls
                                   class="w-full h-full object-cover"></video>

                            <!-- Recording Indicator -->
                            <div x-show="status === 'recording'" 
                                 class="absolute top-4 left-4 flex items-center gap-2 bg-red-600 px-3 py-1.5 rounded-full">
                                <span class="w-3 h-3 bg-white rounded-full animate-pulse"></span>
                                <span class="text-white text-sm font-medium" x-text="formatTime(recordingTime)"></span>
                            </div>

                            <!-- Timer for Prep -->
                            <div x-show="status === 'preparing'" 
                                 class="absolute inset-0 bg-gray-900/80 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-6xl font-bold text-white mb-2" x-text="prepTimeRemaining"></div>
                                    <div class="text-gray-400">Preparation time remaining</div>
                                </div>
                            </div>

                            <!-- Uploading Overlay -->
                            <div x-show="status === 'uploading'" 
                                 class="absolute inset-0 bg-gray-900/80 flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="animate-spin h-12 w-12 text-indigo-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <div class="text-white font-medium">Uploading your response...</div>
                                    <div class="text-gray-400 text-sm mt-1" x-text="uploadProgress + '%'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Controls -->
                        <div class="p-4 border-t border-gray-700">
                            <div class="flex items-center justify-center gap-4">
                                @if($status === 'ready')
                                    <button wire:click="startPreparation" 
                                            class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Start Preparation
                                    </button>
                                @endif

                                @if($status === 'preparing')
                                    <button wire:click="skipPreparation"
                                            class="px-6 py-3 bg-gray-700 text-white font-semibold rounded-lg hover:bg-gray-600 transition">
                                        Skip & Record Now
                                    </button>
                                @endif

                                @if($status === 'recording')
                                    <button @click="stopRecording()"
                                            class="px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <rect x="6" y="6" width="12" height="12" rx="2" />
                                        </svg>
                                        Stop Recording
                                    </button>
                                @endif

                                @if($status === 'reviewing')
                                    <button @click="retake()"
                                            class="px-6 py-3 bg-gray-700 text-white font-semibold rounded-lg hover:bg-gray-600 transition flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Retake ({{ $attemptNumber }}/{{ $currentQuestion?->max_retakes ?? 3 }})
                                    </button>
                                    <button @click="submitRecording()"
                                            class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Submit & Continue
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Question Panel -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-800 rounded-xl p-6 sticky top-6">
                        <div class="flex items-center gap-2 text-indigo-400 text-sm font-medium mb-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Question {{ $currentQuestionIndex + 1 }}
                        </div>
                        
                        @if($currentQuestion)
                            <h3 class="text-xl font-semibold text-white mb-4">
                                {{ $currentQuestion->question_text }}
                            </h3>
                            
                            @if($currentQuestion->question_context)
                                <div class="bg-gray-700/50 rounded-lg p-4 mb-4">
                                    <p class="text-sm text-gray-300">{{ $currentQuestion->question_context }}</p>
                                </div>
                            @endif

                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Prep time: {{ $currentQuestion->prep_time_seconds }} seconds</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Max response: {{ floor($currentQuestion->max_response_time_seconds / 60) }}:{{ str_pad($currentQuestion->max_response_time_seconds % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Retakes allowed: {{ $currentQuestion->max_retakes }}</span>
                                </div>
                            </div>
                        @endif

                        <!-- Tips -->
                        <div class="mt-6 pt-6 border-t border-gray-700">
                            <h4 class="text-sm font-medium text-gray-300 mb-3">Quick Tips</h4>
                            <ul class="space-y-2 text-sm text-gray-400">
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Look directly at the camera
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Speak clearly and at a moderate pace
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Use specific examples
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Error Message -->
    @if($errorMessage)
        <div class="fixed bottom-4 right-4 bg-red-600 text-white px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errorMessage }}</span>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function videoRecorder() {
    return {
        status: @entangle('status'),
        cameraReady: false,
        stream: null,
        mediaRecorder: null,
        recordedChunks: [],
        recordingTime: 0,
        recordingTimer: null,
        prepTimeRemaining: {{ $prepTimeRemaining }},
        prepTimer: null,
        uploadProgress: 0,
        recordedBlob: null,

        async init() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 1280, height: 720, facingMode: 'user' },
                    audio: true
                });
                
                if (this.$refs.previewVideo) {
                    this.$refs.previewVideo.srcObject = this.stream;
                }
                if (this.$refs.recordingVideo) {
                    this.$refs.recordingVideo.srcObject = this.stream;
                }
                
                this.cameraReady = true;
            } catch (error) {
                console.error('Failed to access camera:', error);
                alert('Unable to access camera. Please ensure you have granted camera permissions.');
            }

            // Listen for Livewire events
            Livewire.on('start-prep-timer', (data) => {
                this.startPrepTimer(data.seconds);
            });

            Livewire.on('start-recording', (data) => {
                this.startRecording(data.maxDuration);
            });

            Livewire.on('stop-recording', () => {
                this.stopRecording();
            });

            Livewire.on('reset-recording', () => {
                this.resetRecording();
            });

            Livewire.on('submit-recording', () => {
                this.uploadRecording();
            });
        },

        startPrepTimer(seconds) {
            this.prepTimeRemaining = seconds;
            this.prepTimer = setInterval(() => {
                this.prepTimeRemaining--;
                if (this.prepTimeRemaining <= 0) {
                    clearInterval(this.prepTimer);
                    Livewire.dispatch('prep-timer-complete');
                }
            }, 1000);
        },

        startRecording(maxDuration) {
            this.status = 'recording';
            this.recordedChunks = [];
            this.recordingTime = 0;

            const options = { mimeType: 'video/webm;codecs=vp9,opus' };
            if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                options.mimeType = 'video/webm';
            }

            this.mediaRecorder = new MediaRecorder(this.stream, options);
            
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.recordedChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                this.recordedBlob = new Blob(this.recordedChunks, { type: 'video/webm' });
                const url = URL.createObjectURL(this.recordedBlob);
                this.$refs.playbackVideo.src = url;
                this.status = 'reviewing';
                Livewire.dispatch('recording-stopped', { duration: this.recordingTime });
            };

            this.mediaRecorder.start(1000);

            this.recordingTimer = setInterval(() => {
                this.recordingTime++;
                if (this.recordingTime >= maxDuration) {
                    this.stopRecording();
                }
            }, 1000);
        },

        stopRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
            clearInterval(this.recordingTimer);
        },

        retake() {
            this.recordedChunks = [];
            this.recordedBlob = null;
            this.recordingTime = 0;
            this.$wire.retakeRecording();
        },

        async submitRecording() {
            Livewire.dispatch('upload-started');

            try {
                const formData = new FormData();
                formData.append('video', this.recordedBlob, 'recording.webm');
                formData.append('question_id', '{{ $currentQuestion?->id ?? '' }}');
                formData.append('attempt', '{{ $attemptNumber }}');

                const response = await fetch('{{ route("video-interview.api.upload", $session) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error('Upload failed');
                }

                const data = await response.json();
                
                Livewire.dispatch('upload-complete', {
                    filePath: data.file_path,
                    fileName: data.file_name || 'recording.webm',
                    fileSize: this.recordedBlob.size
                });

            } catch (error) {
                console.error('Upload error:', error);
                Livewire.dispatch('upload-error', { error: error.message });
            }
        },

        async uploadRecording() {
            this.submitRecording();
        },

        resetRecording() {
            this.recordedChunks = [];
            this.recordedBlob = null;
            this.recordingTime = 0;
            this.uploadProgress = 0;
            if (this.$refs.playbackVideo) {
                this.$refs.playbackVideo.src = '';
            }
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        destroy() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            clearInterval(this.recordingTimer);
            clearInterval(this.prepTimer);
        }
    };
}
</script>
@endpush
