@extends('layouts.app')

@section('title', $template->name . ' - Email Template')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <a href="{{ route('email-templates.index') }}" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">← Back to Templates</a>
                <h1 class="text-3xl font-bold text-gray-900">{{ $template->name }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-1 bg-{{ $template->type === 'system' ? 'blue' : ($template->type === 'ai_generated' ? 'purple' : 'gray') }}-100 text-{{ $template->type === 'system' ? 'blue' : ($template->type === 'ai_generated' ? 'purple' : 'gray') }}-700 text-xs rounded-full capitalize">
                        {{ str_replace('_', ' ', $template->type) }}
                    </span>
                    <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs rounded-full capitalize">
                        {{ $template->tone }}
                    </span>
                    <span class="text-sm text-gray-500">{{ $template->usage_count }} uses</span>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                @if($template->user_id === auth()->id())
                <a href="{{ route('email-templates.edit', $template->id) }}" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    ✏️ Edit
                </a>
                @endif
                <button onclick="openAiModal()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    🤖 AI Customize
                </button>
                <button onclick="openSendModal()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    📧 Send Email
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Email Preview -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-orange-100">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="text-sm text-gray-500 mb-1">Subject</div>
                        <div class="font-medium text-gray-900" id="preview-subject">{{ $template->subject }}</div>
                    </div>
                    <div class="p-6">
                        <div id="preview-body" class="prose max-w-none">
                            {!! $template->body_html !!}
                        </div>
                    </div>
                </div>

                <!-- Variables Used -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">📋 Variables in this Template</h2>
                    @if(empty($template->variables))
                    <p class="text-gray-500">No variables used in this template.</p>
                    @else
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($template->variables as $variable => $description)
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <code class="text-orange-600 text-sm font-mono">@{{{{ $variable }}}}</code>
                            <p class="text-xs text-gray-600 mt-1">{{ $description }}</p>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Version History -->
                @if($template->versions->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">📜 Version History</h2>
                    <div class="space-y-3">
                        @foreach($template->versions as $version)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <span class="font-medium text-gray-900">Version {{ $version->version_number }}</span>
                                <span class="text-sm text-gray-500 ml-2">{{ $version->created_at->diffForHumans() }}</span>
                                @if($version->change_notes)
                                <p class="text-sm text-gray-600 mt-1">{{ $version->change_notes }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">⚡ Quick Actions</h2>
                    <div class="space-y-2">
                        <button onclick="duplicateTemplate()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-left">
                            📋 Duplicate Template
                        </button>
                        <button onclick="openPreviewModal()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-left">
                            👁️ Preview with Sample Data
                        </button>
                        <button onclick="copyToClipboard()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-left">
                            📄 Copy HTML
                        </button>
                    </div>
                </div>

                <!-- Analytics -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">📊 Analytics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Open Rate</span>
                                <span class="font-medium text-gray-900">{{ $analytics['totals']['open_rate'] ?? 0 }}%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full" style="width: {{ $analytics['totals']['open_rate'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Click Rate</span>
                                <span class="font-medium text-gray-900">{{ $analytics['totals']['click_rate'] ?? 0 }}%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ $analytics['totals']['click_rate'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $analytics['totals']['sends'] ?? 0 }}</div>
                                <div class="text-xs text-gray-600">Sent</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $analytics['totals']['opens'] ?? 0 }}</div>
                                <div class="text-xs text-gray-600">Opened</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Info -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">ℹ️ Template Info</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Category</dt>
                            <dd class="text-gray-900">{{ $template->category->name ?? 'Uncategorized' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Created</dt>
                            <dd class="text-gray-900">{{ $template->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Last Updated</dt>
                            <dd class="text-gray-900">{{ $template->updated_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div id="send-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">📧 Send Email</h3>
        </div>
        <form id="send-form" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email *</label>
                <input type="email" name="recipient_email" required 
                       class="w-full border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Name *</label>
                <input type="text" name="recipient_name" required 
                       class="w-full border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500">
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <h4 class="font-medium text-gray-900 mb-3">Fill Variables</h4>
                <div class="space-y-3 max-h-48 overflow-y-auto" id="variable-inputs">
                    @foreach($template->variables ?? [] as $variable => $description)
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ $description }}</label>
                        <input type="text" name="variables[{{ $variable }}]" 
                               placeholder="@{{{{ $variable }}}}"
                               class="w-full border-gray-200 rounded-lg text-sm">
                    </div>
                    @endforeach
                </div>
            </div>
        </form>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeSendModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Cancel
            </button>
            <button onclick="sendEmail()" id="send-btn" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                Send Email
            </button>
        </div>
    </div>
</div>

<!-- AI Customization Modal -->
<div id="ai-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">🤖 AI Customize Template</h3>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                <select id="ai-tone" class="w-full border-gray-200 rounded-lg">
                    <option value="professional" {{ $template->tone === 'professional' ? 'selected' : '' }}>Professional</option>
                    <option value="friendly" {{ $template->tone === 'friendly' ? 'selected' : '' }}>Friendly</option>
                    <option value="formal" {{ $template->tone === 'formal' ? 'selected' : '' }}>Formal</option>
                    <option value="casual" {{ $template->tone === 'casual' ? 'selected' : '' }}>Casual</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Focus Area (optional)</label>
                <input type="text" id="ai-focus" class="w-full border-gray-200 rounded-lg" 
                       placeholder="e.g., emphasize company culture, highlight benefits">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Length</label>
                <select id="ai-length" class="w-full border-gray-200 rounded-lg">
                    <option value="shorter">Make it shorter</option>
                    <option value="same" selected>Keep same length</option>
                    <option value="longer">Make it longer</option>
                </select>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeAiModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Cancel
            </button>
            <button onclick="runAiCustomization()" id="ai-btn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                ✨ Generate
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const templateId = {{ $template->id }};

function openSendModal() {
    document.getElementById('send-modal').classList.remove('hidden');
}

function closeSendModal() {
    document.getElementById('send-modal').classList.add('hidden');
}

function openAiModal() {
    document.getElementById('ai-modal').classList.remove('hidden');
}

function closeAiModal() {
    document.getElementById('ai-modal').classList.add('hidden');
}

async function sendEmail() {
    const btn = document.getElementById('send-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Sending...';
    
    const form = document.getElementById('send-form');
    const formData = new FormData(form);
    
    const data = {
        recipient_email: formData.get('recipient_email'),
        recipient_name: formData.get('recipient_name'),
        variables: {}
    };
    
    for (const [key, value] of formData.entries()) {
        if (key.startsWith('variables[')) {
            const varName = key.match(/\[(\w+)\]/)[1];
            data.variables[varName] = value;
        }
    }
    
    try {
        const response = await fetch(`/email-templates/${templateId}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeSendModal();
            alert('Email sent successfully!');
            location.reload();
        } else {
            alert(result.message || 'Failed to send email');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Send Email';
    }
}

async function runAiCustomization() {
    const btn = document.getElementById('ai-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Generating...';
    
    try {
        const response = await fetch(`/email-templates/${templateId}/ai-customize`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                tone: document.getElementById('ai-tone').value,
                focus: document.getElementById('ai-focus').value,
                length: document.getElementById('ai-length').value
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeAiModal();
            document.getElementById('preview-body').innerHTML = result.customized_content;
            
            if (confirm('AI customization generated! Would you like to save this as a new template?')) {
                await fetch(`/email-templates/${templateId}/accept-customization`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        customization_id: result.customization_id,
                        save_as_new: true
                    })
                });
                location.reload();
            }
        } else {
            alert(result.error || 'Failed to customize template');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    } finally {
        btn.disabled = false;
        btn.textContent = '✨ Generate';
    }
}

async function duplicateTemplate() {
    try {
        const response = await fetch(`/email-templates/${templateId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = `/email-templates/${result.template.id}/edit`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function copyToClipboard() {
    const body = document.getElementById('preview-body').innerHTML;
    navigator.clipboard.writeText(body).then(() => {
        alert('HTML copied to clipboard!');
    });
}

function openPreviewModal() {
    // Simple preview with sample data
    const sampleData = {
        candidate_name: 'John Smith',
        candidate_first_name: 'John',
        job_title: 'Senior Software Engineer',
        company_name: 'Acme Corp',
        interview_date: 'December 15, 2025',
        interview_time: '2:00 PM EST'
    };
    
    let body = document.getElementById('preview-body').innerHTML;
    for (const [key, value] of Object.entries(sampleData)) {
        body = body.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), value);
    }
    
    document.getElementById('preview-body').innerHTML = body;
}
</script>
@endpush
