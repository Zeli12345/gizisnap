<?php

namespace App\Http\Controllers;

use App\Exceptions\AiFailure;
use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public const SUGGESTIONS = [
        'Sisa kalori saya hari ini cocok untuk makan apa?',
        'Apakah protein saya hari ini sudah cukup?',
        'Camilan sehat di kantin sekolah apa saja?',
    ];

    public function __construct(private readonly ChatService $chat) {}

    public function index(Request $request)
    {
        return view('chat.index', $this->sidebar($request) + [
            'conversation' => null,
            'messages' => collect(),
            'pending' => null,
        ]);
    }

    public function show(Request $request, ChatConversation $conversation)
    {
        Gate::authorize('view', $conversation);

        return view('chat.index', $this->sidebar($request) + [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->orderBy('id')->get(),
            'pending' => $this->chat->pendingUserMessage($conversation),
        ]);
    }

    /**
     * Percakapan baru dengan pesan pertama.
     */
    public function store(ChatMessageRequest $request)
    {
        $conversation = $this->chat->startConversation($request->user(), $request->validated('message'));

        return $this->sendTo($request, $conversation, $request->validated('message'));
    }

    public function send(ChatMessageRequest $request, ChatConversation $conversation)
    {
        Gate::authorize('update', $conversation);

        return $this->sendTo($request, $conversation, $request->validated('message'));
    }

    /**
     * Minta ulang jawaban untuk pesan terakhir yang belum dijawab.
     */
    public function retry(Request $request, ChatConversation $conversation)
    {
        Gate::authorize('update', $conversation);

        $pending = $this->chat->pendingUserMessage($conversation);
        abort_unless($pending, 409, 'Tidak ada pesan yang perlu dikirim ulang.');

        try {
            $assistant = $this->chat->reply($conversation, $pending);
        } catch (AiFailure $e) {
            return $this->failure($request, $conversation, $e, null);
        }

        return $request->expectsJson()
            ? response()->json(['assistant' => $this->present($assistant)])
            : redirect()->route('chat.show', $conversation);
    }

    public function destroy(ChatConversation $conversation)
    {
        Gate::authorize('delete', $conversation);
        $conversation->delete();

        return redirect()->route('chat.index')->with('success', 'Percakapan dihapus.');
    }

    private function sendTo(Request $request, ChatConversation $conversation, string $text)
    {
        try {
            ['user' => $user, 'assistant' => $assistant] = $this->chat->send($conversation, $text);
        } catch (AiFailure $e) {
            $userMessage = $e->reason === 'quota' ? null : $this->chat->pendingUserMessage($conversation);

            if ($e->reason === 'quota' && $conversation->messages()->doesntExist()) {
                $conversation->delete();
                $conversation = null;
            }

            return $this->failure($request, $conversation, $e, $userMessage);
        }

        if (! $request->expectsJson()) {
            return redirect()->route('chat.show', $conversation);
        }

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'url' => route('chat.show', $conversation),
                'send_url' => route('chat.send', $conversation),
                'retry_url' => route('chat.retry', $conversation),
            ],
            'user' => $this->present($user),
            'assistant' => $this->present($assistant),
        ]);
    }

    private function failure(Request $request, ?ChatConversation $conversation, AiFailure $e, ?ChatMessage $userMessage)
    {
        if (! $request->expectsJson()) {
            return $conversation
                ? redirect()->route('chat.show', $conversation)->with('error', $e->getMessage())
                : redirect()->route('chat.index')->with('error', $e->getMessage());
        }

        return response()->json([
            'message' => $e->getMessage(),
            'reason' => $e->reason,
            'conversation' => $conversation ? [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'url' => route('chat.show', $conversation),
                'send_url' => route('chat.send', $conversation),
                'retry_url' => route('chat.retry', $conversation),
            ] : null,
            'user' => $userMessage ? $this->present($userMessage) : null,
        ], $e->reason === 'quota' ? 429 : 503);
    }

    private function present(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'html' => Str::markdown($message->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            'time' => $message->created_at->format('H:i'),
        ];
    }

    private function sidebar(Request $request): array
    {
        $user = $request->user();

        return [
            'conversations' => $user->chatConversations()->orderByDesc('last_message_at')->take(30)->get(),
            'suggestions' => self::SUGGESTIONS,
        ];
    }
}
