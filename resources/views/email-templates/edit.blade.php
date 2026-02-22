@extends('layouts.app')

@section('title', 'Edit: ' . $template->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('email-templates.show', $template->id) }}" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">← Back to Template</a>
            <h1 class="text-3xl font-bold text-gray-900">✏️ Edit Template</h1>
            <p class="text-gray-600">{{ $template->name }}</p>
        </div>

        <form id="template-form" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Basic Info -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4">📝 Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                        <input type="text" name="name" required value="{{ $template->name }}"
                               class="w-full border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select name="category_id" required class="w-full border-gray-200 rounded-lg">
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $template->category_id == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                        <select name="tone" class="w-full border-gray-200 rounded-lg">
                            <option value="professional" {{ $template->tone === 'professional' ? 'selected' : '' }}>Professional</option>
                            <option value="friendly" {{ $template->tone === 'friendly' ? 'selected' : '' }}>Friendly</option>
                            <option value="formal" {{ $template->tone === 'formal' ? 'selected' : '' }}>Formal</option>
                            <option value="casual" {{ $template->tone === 'casual' ? 'selected' : '' }}>Casual</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-6">
                        <input type="checkbox" name="is_public" id="is_public" 
                               {{ $template->is_public ? 'checked' : '' }}
                               class="rounded border-gray-300 text-orange-600">
                        <label for="is_public" class="text-sm text-gray-700">Share with team</label>
                    </div>
                </div>
            </div>

            <!-- Subject Line -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4">📬 Subject Line</h2>
                <input type="text" name="subject" required value="{{ $template->subject }}"
                       class="w-full border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500">
                <p class="text-xs text-gray-500 mt-2">Use @{{variable_name}} for dynamic content</p>
            </div>

            <!-- Email Body -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">📄 Email Body</h2>
                    <button type="button" onclick="insertVariable()" class="text-sm px-3 py-1 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200">
                        + Insert Variable
                    </button>
                </div>
                <textarea name="body_html" id="body-editor" rows="15" required
                          class="w-full border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500 font-mono text-sm">{{ $template->body_html }}</textarea>
            </div>

            <!-- Change Notes -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4">📝 Change Notes</h2>
                <input type="text" name="change_notes" 
                       class="w-full border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500"
                       placeholder="Briefly describe what you changed (optional)">
            </div>

            <!-- Variables Reference -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4">📋 Available Variables</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach($availableVariables as $variable => $description)
                    <button type="button" onclick="insertVar('{{ $variable }}')" 
                            class="p-2 bg-gray-50 rounded-lg text-left hover:bg-orange-50 transition">
                        <code class="text-orange-600 text-xs">@{{{{ $variable }}}}</code>
                        <p class="text-xs text-gray-500 truncate">{{ $description }}</p>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Preview Section -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">👁️ Preview</h2>
                    <button type="button" onclick="refreshPreview()" class="text-sm px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        🔄 Refresh Preview
                    </button>
                </div>
                <div id="preview-container" class="border border-gray-200 rounded-lg p-6 min-h-[200px]">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between">
                <button type="button" onclick="deleteTemplate()" class="px-6 py-3 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                    🗑️ Delete Template
                </button>
                <div class="flex gap-4">
                    <a href="{{ route('email-templates.show', $template->id) }}" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Variable Insert Modal -->
<div id="variable-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full max-h-[80vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Insert Variable</h3>
            <button onclick="closeVariableModal()" class="p-2 hover:bg-gray-100 rounded-lg">✕</button>
        </div>
        <div class="p-6 max-h-[60vh] overflow-y-auto">
            <div class="space-y-2">
                @foreach($availableVariables as $variable => $description)
                <button onclick="insertVar('{{ $variable }}'); closeVariableModal();" 
                        class="w-full p-3 bg-gray-50 rounded-lg text-left hover:bg-orange-50 transition flex justify-between items-center">
                    <div>
                        <code class="text-orange-600 font-mono">@{{{{ $variable }}}}</code>
                        <p class="text-sm text-gray-600">{{ $description }}</p>
                    </div>
                    <span class="text-gray-400">→</span>
                </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const templateId = {{ $template->id }};
const editor = document.getElementById('body-editor');

document.addEventListener('DOMContentLoaded', function() {
    refreshPreview();
    
    editor.addEventListener('input', debounce(refreshPreview, 500));
    document.querySelector('input[name="subject"]').addEventListener('input', debounce(refreshPreview, 500));

    document.getElementById('template-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            name: formData.get('name'),
            category_id: formData.get('category_id'),
            subject: formData.get('subject'),
            body_html: formData.get('body_html'),
            tone: formData.get('tone'),
            is_public: formData.get('is_public') === 'on',
            change_notes: formData.get('change_notes')
        };
        
        try {
            const response = await fetch(`/email-templates/${templateId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = `/email-templates/${templateId}`;
            } else {
                alert('Error updating template');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred');
        }
    });
});

function insertVariable() {
    document.getElementById('variable-modal').classList.remove('hidden');
}

function closeVariableModal() {
    document.getElementById('variable-modal').classList.add('hidden');
}

function insertVar(variable) {
    const textarea = editor;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const insertion = `{{${variable}}}`;
    
    textarea.value = text.substring(0, start) + insertion + text.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + insertion.length;
    textarea.focus();
    
    refreshPreview();
}

function refreshPreview() {
    const subject = document.querySelector('input[name="subject"]').value;
    const body = editor.value;
    
    const sampleData = {
        candidate_name: 'John Smith',
        candidate_first_name: 'John',
        candidate_email: 'john@example.com',
        job_title: 'Senior Software Engineer',
        company_name: 'Acme Corp',
        interviewer_name: 'Jane Doe',
        interview_date: 'December 15, 2025',
        interview_time: '2:00 PM EST',
        interview_location: 'Conference Room A / Zoom Link',
        interview_type: 'Video Interview',
        offer_salary: '$120,000',
        offer_start_date: 'January 15, 2026',
        offer_deadline: 'December 20, 2025',
        sender_name: 'HR Team',
        sender_title: 'Talent Acquisition',
        sender_email: 'hr@acmecorp.com',
        sender_phone: '(555) 123-4567',
        next_steps: 'We will review your application and get back to you within 5 business days.'
    };
    
    let previewHtml = body;
    for (const [key, value] of Object.entries(sampleData)) {
        previewHtml = previewHtml.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), `<span class="bg-orange-100 text-orange-800 px-1 rounded">${value}</span>`);
    }
    
    const container = document.getElementById('preview-container');
    container.innerHTML = `
        <div class="mb-4 pb-4 border-b border-gray-200">
            <span class="text-xs text-gray-500">Subject:</span>
            <p class="font-medium text-gray-900">${subject || 'No subject'}</p>
        </div>
        <div class="prose max-w-none">${previewHtml}</div>
    `;
}

async function deleteTemplate() {
    if (!confirm('Are you sure you want to delete this template? This action cannot be undone.')) return;
    
    try {
        const response = await fetch(`/email-templates/${templateId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '/email-templates';
        } else {
            alert(result.error || 'Failed to delete template');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endpush
