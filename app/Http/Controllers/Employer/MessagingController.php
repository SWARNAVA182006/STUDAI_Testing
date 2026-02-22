<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Http\Request;

class MessagingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:employer');
    }
    
    /**
     * Show inbox with conversations
     */
    public function index(Request $request)
    {
        $company = auth('employer')->user()->company;
        $status = $request->input('status', 'active');
        $search = $request->input('search');
        
        $query = Conversation::where('company_id', $company->id)
            ->with(['candidate:id,name,email', 'job:id,title', 'latestMessage'])
            ->withCount(['messages', 'unreadMessages']);
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($search) {
            $query->whereHas('candidate', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $conversations = $query->orderByDesc('last_message_at')
            ->paginate(20);
        
        $stats = [
            'total' => Conversation::where('company_id', $company->id)->count(),
            'unread' => Conversation::where('company_id', $company->id)
                ->whereHas('messages', function ($q) {
                    $q->where('is_read', false);
                })
                ->count(),
        ];
        
        return view('employer.messages.index', compact('conversations', 'stats', 'status', 'search'));
    }
    
    /**
     * Show conversation thread
     */
    public function show(Conversation $conversation)
    {
        $company = auth('employer')->user()->company;
        
        if ($conversation->company_id !== $company->id) {
            abort(403);
        }
        
        $conversation->load([
            'candidate.profile',
            'job:id,title',
            'application:id,status,match_score',
            'messages.sender:id,name,account_type'
        ]);
        
        // Mark messages as read
        $conversation->messages()
            ->where('is_read', false)
            ->whereHas('sender', function ($q) {
                $q->where('account_type', 'job_seeker');
            })
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        
        return view('employer.messages.conversation', compact('conversation'));
    }
    
    /**
     * Send message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'nullable|exists:conversations,id',
            'candidate_id' => 'required_without:conversation_id|exists:users,id',
            'job_id' => 'nullable|exists:jobs,id',
            'subject' => 'required_without:conversation_id|string|max:255',
            'body' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);
        
        $employer = auth('employer')->user();
        $company = $employer->company;
        
        // Get or create conversation
        if (isset($validated['conversation_id'])) {
            $conversation = Conversation::where('company_id', $company->id)
                ->findOrFail($validated['conversation_id']);
        } else {
            $conversation = Conversation::create([
                'company_id' => $company->id,
                'candidate_id' => $validated['candidate_id'],
                'job_id' => $validated['job_id'] ?? null,
                'subject' => $validated['subject'],
                'last_message_at' => now(),
            ]);
        }
        
        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('message-attachments', 'private');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }
        
        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $employer->id,
            'body' => $validated['body'],
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);
        
        // Update conversation
        $conversation->update(['last_message_at' => now()]);
        
        // Dispatch event — notification sent via NotifyOnMessagingAndReferral subscriber
        $candidate = User::find($conversation->candidate_id);
        event(new \App\Events\MessageSent($candidate, $conversation, $validated['body']));
        
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message->load('sender:id,name'),
        ]);
    }
    
    /**
     * Get message templates
     */
    public function templates(Request $request)
    {
        $company = auth('employer')->user()->company;
        $category = $request->input('category');
        
        $query = MessageTemplate::where('company_id', $company->id)
            ->orWhere('is_active', true);
        
        if ($category) {
            $query->where('category', $category);
        }
        
        $templates = $query->get();
        
        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }
    
    /**
     * Save message template
     */
    public function saveTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'category' => 'nullable|in:rejection,interview_invite,offer,follow_up,general',
            'variables' => 'nullable|array',
        ]);
        
        $employer = auth('employer')->user();
        
        $template = MessageTemplate::create([
            'company_id' => $employer->company->id,
            'created_by' => $employer->id,
            'name' => $validated['name'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            'category' => $validated['category'] ?? 'general',
            'variables' => $validated['variables'] ?? [],
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Template saved successfully',
            'template' => $template,
        ]);
    }
    
    /**
     * Archive conversation
     */
    public function archive(Conversation $conversation)
    {
        $company = auth('employer')->user()->company;
        
        if ($conversation->company_id !== $company->id) {
            abort(403);
        }
        
        $conversation->update(['status' => 'archived']);
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation archived',
        ]);
    }
    
    /**
     * Mark as spam
     */
    public function markAsSpam(Conversation $conversation)
    {
        $company = auth('employer')->user()->company;
        
        if ($conversation->company_id !== $company->id) {
            abort(403);
        }
        
        $conversation->update(['status' => 'spam']);
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as spam',
        ]);
    }
    
    /**
     * Unarchive conversation
     */
    public function unarchive(Conversation $conversation)
    {
        $company = auth('employer')->user()->company;
        
        if ($conversation->company_id !== $company->id) {
            abort(403);
        }
        
        $conversation->update(['status' => 'active']);
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation restored',
        ]);
    }
}
