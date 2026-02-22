@extends('layouts.app')

@section('title', 'Email Template Library')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📧 Email Template Library</h1>
                <p class="text-gray-600">Professional templates for every hiring stage</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                <a href="{{ route('email-templates.user-analytics') }}" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    📊 My Analytics
                </a>
                <a href="{{ route('email-templates.create') }}" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    + Create Template
                </a>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-8 border border-orange-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <input type="text" id="search-templates" placeholder="Search templates..." 
                           class="w-full border-gray-200 rounded-lg text-sm focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <select id="category-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select id="tone-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Tones</option>
                        <option value="professional">Professional</option>
                        <option value="friendly">Friendly</option>
                        <option value="formal">Formal</option>
                        <option value="casual">Casual</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-2xl font-bold text-orange-600">{{ $categories->sum(fn($c) => $c->templates->count()) }}</div>
                <div class="text-xs text-gray-600">Total Templates</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $userTemplates->count() }}</div>
                <div class="text-xs text-gray-600">My Templates</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $categories->count() }}</div>
                <div class="text-xs text-gray-600">Categories</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-2xl font-bold text-purple-600">{{ count($availableVariables) }}</div>
                <div class="text-xs text-gray-600">Variables</div>
            </div>
        </div>

        <!-- Categories & Templates -->
        <div class="space-y-8">
            @foreach($categories as $category)
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-orange-100 category-section" data-category="{{ $category->id }}">
                <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span>{{ $category->icon ?? '📁' }}</span>
                        {{ $category->name }}
                        <span class="bg-white/20 px-2 py-1 rounded-full text-sm">{{ $category->templates->count() }}</span>
                    </h2>
                    @if($category->description)
                    <p class="text-white/80 text-sm mt-1">{{ $category->description }}</p>
                    @endif
                </div>
                
                <div class="p-6">
                    @if($category->templates->isEmpty())
                    <p class="text-center text-gray-500 py-8">No templates in this category yet.</p>
                    @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($category->templates as $template)
                        <div class="template-card border border-gray-200 rounded-xl p-4 hover:shadow-lg hover:border-orange-300 transition cursor-pointer"
                             data-template-id="{{ $template->id }}"
                             data-tone="{{ $template->tone }}">
                            <div class="flex items-start justify-between mb-3">
                                <h3 class="font-semibold text-gray-900">{{ $template->name }}</h3>
                                @if($template->type === 'system')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">System</span>
                                @elseif($template->type === 'ai_generated')
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">AI</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $template->subject }}</p>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="capitalize">{{ $template->tone }}</span>
                                <span>{{ $template->usage_count }} uses</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- My Templates Section -->
        @if($userTemplates->isNotEmpty())
        <div class="mt-8 bg-white rounded-2xl shadow-lg overflow-hidden border border-orange-100">
            <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>👤</span>
                    My Templates
                    <span class="bg-white/20 px-2 py-1 rounded-full text-sm">{{ $userTemplates->count() }}</span>
                </h2>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($userTemplates as $template)
                    <div class="template-card border border-gray-200 rounded-xl p-4 hover:shadow-lg hover:border-orange-300 transition cursor-pointer"
                         data-template-id="{{ $template->id }}"
                         data-tone="{{ $template->tone }}">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-semibold text-gray-900">{{ $template->name }}</h3>
                            <div class="flex gap-1">
                                <button onclick="event.stopPropagation(); editTemplate({{ $template->id }})" 
                                        class="p-1 hover:bg-gray-100 rounded">✏️</button>
                                <button onclick="event.stopPropagation(); deleteTemplate({{ $template->id }})" 
                                        class="p-1 hover:bg-gray-100 rounded">🗑️</button>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $template->subject }}</p>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span class="capitalize">{{ $template->tone }}</span>
                            <span>{{ $template->usage_count }} uses</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Variables Reference -->
        <div class="mt-8 bg-white rounded-2xl shadow-lg overflow-hidden border border-orange-100">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between cursor-pointer" onclick="toggleVariables()">
                <h2 class="text-xl font-bold text-gray-900">📋 Available Variables</h2>
                <span id="variables-toggle" class="text-gray-500">▼</span>
            </div>
            <div id="variables-list" class="p-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($availableVariables as $variable => $description)
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <code class="text-orange-600 text-sm font-mono">@{{{{ $variable }}}}</code>
                        <p class="text-xs text-gray-600 mt-1">{{ $description }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Preview Modal -->
<div id="preview-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900" id="modal-title">Template Preview</h3>
            <button onclick="closeModal()" class="p-2 hover:bg-gray-100 rounded-lg">✕</button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <div id="modal-subject" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <label class="text-xs text-gray-500">Subject</label>
                <p class="font-medium text-gray-900"></p>
            </div>
            <div id="modal-body" class="prose max-w-none"></div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between gap-3">
            <div class="flex gap-2">
                <button onclick="aiCustomize()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    🤖 AI Customize
                </button>
                <button onclick="duplicateTemplate()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    📋 Duplicate
                </button>
            </div>
            <div class="flex gap-2">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Close
                </button>
                <button onclick="useTemplate()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    Use Template
                </button>
            </div>
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
                    <option value="professional">Professional</option>
                    <option value="friendly">Friendly</option>
                    <option value="formal">Formal</option>
                    <option value="casual">Casual</option>
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
            <button onclick="runAiCustomization()" id="ai-submit-btn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                ✨ Generate
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentTemplateId = null;
let currentTemplate = null;

document.addEventListener('DOMContentLoaded', function() {
    // Template card click
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', function() {
            const templateId = this.dataset.templateId;
            openTemplatePreview(templateId);
        });
    });

    // Search functionality
    document.getElementById('search-templates').addEventListener('input', filterTemplates);
    document.getElementById('category-filter').addEventListener('change', filterTemplates);
    document.getElementById('tone-filter').addEventListener('change', filterTemplates);
});

function filterTemplates() {
    const search = document.getElementById('search-templates').value.toLowerCase();
    const category = document.getElementById('category-filter').value;
    const tone = document.getElementById('tone-filter').value;

    document.querySelectorAll('.category-section').forEach(section => {
        if (category && section.dataset.category !== category) {
            section.style.display = 'none';
        } else {
            section.style.display = 'block';
        }
    });

    document.querySelectorAll('.template-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        const cardTone = card.dataset.tone;
        
        let show = true;
        if (search && !text.includes(search)) show = false;
        if (tone && cardTone !== tone) show = false;
        
        card.style.display = show ? 'block' : 'none';
    });
}

function toggleVariables() {
    const list = document.getElementById('variables-list');
    const toggle = document.getElementById('variables-toggle');
    list.classList.toggle('hidden');
    toggle.textContent = list.classList.contains('hidden') ? '▼' : '▲';
}

async function openTemplatePreview(templateId) {
    currentTemplateId = templateId;
    
    try {
        const response = await fetch(`/email-templates/${templateId}/data`);
        const data = await response.json();
        currentTemplate = data.template;
        
        document.getElementById('modal-title').textContent = currentTemplate.name;
        document.getElementById('modal-subject').querySelector('p').textContent = currentTemplate.subject;
        document.getElementById('modal-body').innerHTML = currentTemplate.body_html;
        
        document.getElementById('preview-modal').classList.remove('hidden');
    } catch (error) {
        console.error('Error loading template:', error);
    }
}

function closeModal() {
    document.getElementById('preview-modal').classList.add('hidden');
    currentTemplateId = null;
    currentTemplate = null;
}

function aiCustomize() {
    closeModal();
    document.getElementById('ai-modal').classList.remove('hidden');
}

function closeAiModal() {
    document.getElementById('ai-modal').classList.add('hidden');
}

async function runAiCustomization() {
    const btn = document.getElementById('ai-submit-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Generating...';
    
    try {
        const response = await fetch(`/email-templates/${currentTemplateId}/ai-customize`, {
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
        
        const data = await response.json();
        
        if (data.success) {
            closeAiModal();
            document.getElementById('modal-body').innerHTML = data.customized_content;
            document.getElementById('preview-modal').classList.remove('hidden');
        } else {
            alert(data.error || 'Failed to customize template');
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
        const response = await fetch(`/email-templates/${currentTemplateId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Template duplicated! Redirecting to edit...');
            window.location.href = `/email-templates/${data.template.id}/edit`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function useTemplate() {
    window.location.href = `/email-templates/${currentTemplateId}`;
}

function editTemplate(id) {
    window.location.href = `/email-templates/${id}/edit`;
}

async function deleteTemplate(id) {
    if (!confirm('Are you sure you want to delete this template?')) return;
    
    try {
        const response = await fetch(`/email-templates/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete template');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>
@endpush
