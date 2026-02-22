<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateCategory;
use App\Services\EmailTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function __construct(
        protected EmailTemplateService $templateService
    ) {}

    /**
     * Display template library.
     */
    public function index(): View
    {
        $categories = $this->templateService->getCategories();
        $userTemplates = $this->templateService->getTemplatesForUser(Auth::id());

        return view('email-templates.index', [
            'categories' => $categories,
            'userTemplates' => $userTemplates,
            'availableVariables' => $this->templateService->getAvailableVariables(),
        ]);
    }

    /**
     * Get templates for a category (API).
     */
    public function byCategory(int $categoryId): JsonResponse
    {
        $templates = $this->templateService->getTemplatesForUser(Auth::id(), $categoryId);

        return response()->json([
            'templates' => $templates,
        ]);
    }

    /**
     * Show template creation form.
     */
    public function create(): View
    {
        $categories = EmailTemplateCategory::active()->ordered()->get();

        return view('email-templates.create', [
            'categories' => $categories,
            'availableVariables' => $this->templateService->getAvailableVariables(),
        ]);
    }

    /**
     * Store a new template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:email_template_categories,id',
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'tone' => 'nullable|in:professional,friendly,formal,casual',
            'is_public' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['type'] = 'custom';

        $template = $this->templateService->createTemplate($validated);

        return response()->json([
            'success' => true,
            'template' => $template,
            'message' => 'Template created successfully!',
        ]);
    }

    /**
     * Show template details.
     */
    public function show(int $id): View
    {
        $template = $this->templateService->getTemplate($id);

        if (!$template) {
            abort(404);
        }

        $analytics = $this->templateService->getTemplateAnalytics($id);

        return view('email-templates.show', [
            'template' => $template,
            'analytics' => $analytics,
            'availableVariables' => $this->templateService->getAvailableVariables(),
        ]);
    }

    /**
     * Get template data (API).
     */
    public function getData(int $id): JsonResponse
    {
        $template = $this->templateService->getTemplate($id);

        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        return response()->json([
            'template' => $template,
            'variables' => $template->getVariablesList(),
        ]);
    }

    /**
     * Show template edit form.
     */
    public function edit(int $id): View
    {
        $template = EmailTemplate::findOrFail($id);

        // Check ownership or if it's a system template being duplicated
        if ($template->user_id !== Auth::id() && $template->type !== 'system') {
            abort(403);
        }

        $categories = EmailTemplateCategory::active()->ordered()->get();

        return view('email-templates.edit', [
            'template' => $template,
            'categories' => $categories,
            'availableVariables' => $this->templateService->getAvailableVariables(),
        ]);
    }

    /**
     * Update a template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        if ($template->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:email_template_categories,id',
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'body_html' => 'sometimes|string',
            'body_text' => 'nullable|string',
            'tone' => 'nullable|in:professional,friendly,formal,casual',
            'is_public' => 'boolean',
            'change_notes' => 'nullable|string|max:500',
        ]);

        $changeNotes = $validated['change_notes'] ?? null;
        unset($validated['change_notes']);

        $template = $this->templateService->updateTemplate(
            $template,
            $validated,
            Auth::id(),
            $changeNotes
        );

        return response()->json([
            'success' => true,
            'template' => $template,
            'message' => 'Template updated successfully!',
        ]);
    }

    /**
     * Delete a template.
     */
    public function destroy(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        if ($template->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($template->type === 'system') {
            return response()->json(['error' => 'Cannot delete system templates'], 400);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully!',
        ]);
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $newTemplate = $this->templateService->duplicateTemplate($template, Auth::id());

        return response()->json([
            'success' => true,
            'template' => $newTemplate,
            'message' => 'Template duplicated successfully!',
        ]);
    }

    /**
     * Preview template with variables.
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $variables = $request->input('variables', []);
        $parsed = $this->templateService->parseTemplate($template, $variables);

        return response()->json([
            'subject' => $parsed['subject'],
            'body_html' => $parsed['body_html'],
            'body_text' => $parsed['body_text'],
        ]);
    }

    /**
     * AI customize a template.
     */
    public function aiCustomize(Request $request, int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $params = $request->validate([
            'tone' => 'nullable|in:professional,friendly,formal,casual',
            'focus' => 'nullable|string|max:255',
            'length' => 'nullable|in:shorter,same,longer',
            'context' => 'nullable|array',
        ]);

        $result = $this->templateService->aiCustomize($template, Auth::id(), $params);

        return response()->json($result);
    }

    /**
     * Accept AI customization.
     */
    public function acceptCustomization(Request $request, int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $validated = $request->validate([
            'customization_id' => 'required|exists:email_ai_customizations,id',
            'save_as_new' => 'boolean',
        ]);

        $customization = $template->aiCustomizations()->findOrFail($validated['customization_id']);
        $customization->markAsAccepted();

        if ($validated['save_as_new'] ?? false) {
            // Create new template with customized content
            $newTemplate = $this->templateService->duplicateTemplate($template, Auth::id());
            $newTemplate->update([
                'name' => $template->name . ' (AI Customized)',
                'body_html' => $customization->customized_content,
                'type' => 'ai_generated',
            ]);

            return response()->json([
                'success' => true,
                'template' => $newTemplate,
                'message' => 'New template created with customization!',
            ]);
        }

        // Update existing template
        if ($template->user_id === Auth::id()) {
            $template->update(['body_html' => $customization->customized_content]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customization applied!',
        ]);
    }

    /**
     * Send email using template.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $validated = $request->validate([
            'recipient_email' => 'required|email',
            'recipient_name' => 'required|string|max:255',
            'variables' => 'nullable|array',
        ]);

        $emailSend = $this->templateService->sendEmail(
            $template,
            $validated['recipient_email'],
            $validated['recipient_name'],
            $validated['variables'] ?? [],
            Auth::id(),
            Auth::user()->company_id ?? null
        );

        return response()->json([
            'success' => $emailSend->status !== 'failed',
            'email_send' => $emailSend,
            'message' => $emailSend->status === 'failed'
                ? 'Failed to send email: ' . $emailSend->error_message
                : 'Email sent successfully!',
        ]);
    }

    /**
     * Get template analytics.
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        $days = $request->input('days', 30);
        $analytics = $this->templateService->getTemplateAnalytics($id, $days);

        return response()->json($analytics);
    }

    /**
     * Get user's email analytics.
     */
    public function userAnalytics(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $analytics = $this->templateService->getUserAnalytics(Auth::id(), $days);

        return response()->json($analytics);
    }

    /**
     * Get available variables.
     */
    public function variables(): JsonResponse
    {
        return response()->json([
            'variables' => $this->templateService->getAvailableVariables(),
        ]);
    }
}
