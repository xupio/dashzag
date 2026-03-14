<?php

namespace App\Http\Controllers;

use App\Models\InternalMessage;
use App\Models\InternalMessageRecipient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InternalMailController extends Controller
{
    public function inbox(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $query = $this->recipientMailboxQuery($user, $search)
            ->whereNull('deleted_at');

        $messages = $query->latest('created_at')->paginate(12)->withQueryString();

        return view('pages.email.inbox', [
            'folder' => 'inbox',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'mailIdentity' => $user->email,
        ]);
    }

    public function starred(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $messages = $this->recipientMailboxQuery($user, $search)
            ->whereNull('deleted_at')
            ->whereNotNull('starred_at')
            ->latest('starred_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.email.inbox', [
            'folder' => 'starred',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'mailIdentity' => $user->email,
        ]);
    }

    public function archived(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $messages = $this->recipientMailboxQuery($user, $search)
            ->whereNotNull('deleted_at')
            ->latest('deleted_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.email.inbox', [
            'folder' => 'archived',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'mailIdentity' => $user->email,
        ]);
    }

    public function sent(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $query = InternalMessage::query()
            ->with(['recipients.user', 'sender'])
            ->where('sender_id', $user->id);

        if ($search !== '') {
            $query->where(function ($messageQuery) use ($search) {
                $messageQuery->where('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('recipients.user', fn ($recipientQuery) => $recipientQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $messages = $query->latest()->paginate(12)->withQueryString();

        return view('pages.email.sent', [
            'folder' => 'sent',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'mailIdentity' => $user->email,
        ]);
    }

    public function compose(Request $request): View
    {
        $user = $request->user();
        $replyMessage = null;
        $prefillTo = collect();
        $prefillCc = collect();
        $prefillSubject = '';
        $replyContext = null;

        if ($request->filled('reply')) {
            $replyMessage = InternalMessage::query()
                ->with(['sender', 'recipients.user'])
                ->findOrFail((int) $request->integer('reply'));

            abort_unless($this->userCanAccessMessage($user, $replyMessage), 403);

            $replyContext = 'thread';
            $prefillTo = collect([$replyMessage->sender_id])
                ->merge($replyMessage->toRecipients->pluck('user_id'))
                ->reject(fn ($id) => (int) $id === $user->id)
                ->unique()
                ->values();

            $prefillCc = $replyMessage->ccRecipients
                ->pluck('user_id')
                ->reject(fn ($id) => (int) $id === $user->id)
                ->reject(fn ($id) => $prefillTo->contains((int) $id))
                ->unique()
                ->values();

            $prefillSubject = str($replyMessage->subject)->startsWith('Re: ') ? $replyMessage->subject : 'Re: '.$replyMessage->subject;
        }

        $users = User::query()
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('pages.email.compose', [
            'folder' => 'compose',
            'messageCounts' => $this->messageCounts($user),
            'mailIdentity' => $user->email,
            'users' => $users,
            'replyMessage' => $replyMessage,
            'prefillTo' => $prefillTo,
            'prefillCc' => $prefillCc,
            'prefillSubject' => $prefillSubject,
            'replyContext' => $replyContext,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $toRecipients = collect($validated['to'])->map(fn ($id) => (int) $id);
        $ccRecipients = collect($validated['cc'] ?? [])->map(fn ($id) => (int) $id)->reject(fn ($id) => $toRecipients->contains($id));

        DB::transaction(function () use ($user, $validated, $toRecipients, $ccRecipients) {
            $message = InternalMessage::create([
                'sender_id' => $user->id,
                'subject' => $validated['subject'],
                'body' => $validated['body'],
            ]);

            $recipientRows = $toRecipients->map(fn ($recipientId) => [
                'user_id' => $recipientId,
                'recipient_type' => 'to',
            ])->merge(
                $ccRecipients->map(fn ($recipientId) => [
                    'user_id' => $recipientId,
                    'recipient_type' => 'cc',
                ])
            );

            foreach ($recipientRows as $recipientRow) {
                $message->recipients()->create($recipientRow);
            }
        });

        return redirect()
            ->route('email.sent')
            ->with('mail_success', 'Your internal email has been sent successfully.');
    }

    public function reply(Request $request, InternalMessage $message): RedirectResponse
    {
        $user = $request->user();

        abort_unless($this->userCanAccessMessage($user, $message), 403);

        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $message->loadMissing(['sender', 'recipients.user']);

        $toRecipients = collect([$message->sender_id])
            ->merge($message->toRecipients->pluck('user_id'))
            ->reject(fn ($id) => (int) $id === $user->id)
            ->unique()
            ->values();

        $ccRecipients = $message->ccRecipients
            ->pluck('user_id')
            ->reject(fn ($id) => (int) $id === $user->id)
            ->reject(fn ($id) => $toRecipients->contains((int) $id))
            ->unique()
            ->values();

        abort_if($toRecipients->isEmpty() && $ccRecipients->isEmpty(), 422, 'No recipients available for reply.');

        $reply = DB::transaction(function () use ($user, $message, $validated, $toRecipients, $ccRecipients) {
            $replyMessage = InternalMessage::create([
                'sender_id' => $user->id,
                'thread_root_id' => $message->thread_root_id ?: $message->id,
                'reply_to_message_id' => $message->id,
                'subject' => str($message->subject)->startsWith('Re: ') ? $message->subject : 'Re: '.$message->subject,
                'body' => $validated['body'],
            ]);

            foreach ($toRecipients as $recipientId) {
                $replyMessage->recipients()->create([
                    'user_id' => $recipientId,
                    'recipient_type' => 'to',
                ]);
            }

            foreach ($ccRecipients as $recipientId) {
                $replyMessage->recipients()->create([
                    'user_id' => $recipientId,
                    'recipient_type' => 'cc',
                ]);
            }

            return $replyMessage;
        });

        return redirect()
            ->route('email.sent.read', $reply)
            ->with('mail_success', 'Your reply has been sent successfully.');
    }

    public function toggleStar(Request $request, InternalMessageRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->toggleStar();

        return back()->with('mail_success', $recipient->starred_at ? 'Message starred.' : 'Message removed from starred.');
    }

    public function toggleRead(Request $request, InternalMessageRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->toggleReadState();

        return back()->with('mail_success', $recipient->read_at ? 'Message marked as read.' : 'Message marked as unread.');
    }

    public function archive(Request $request, InternalMessageRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->archive();

        return redirect()->route('email.inbox')->with('mail_success', 'Message moved to archive.');
    }

    public function showInbox(Request $request, InternalMessageRecipient $recipient): View
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->load(['message.sender', 'message.recipients.user']);
        $recipient->markAsRead();

        return view('pages.email.read', [
            'folder' => 'inbox',
            'messageCounts' => $this->messageCounts($request->user()),
            'mailIdentity' => $request->user()->email,
            'message' => $recipient->message,
            'recipientRecord' => $recipient,
            'readContext' => 'inbox',
            'threadMessages' => $this->threadMessagesFor($request->user(), $recipient->message),
        ]);
    }

    public function showSent(Request $request, InternalMessage $message): View
    {
        abort_unless($message->sender_id === $request->user()->id, 403);

        $message->load(['sender', 'recipients.user']);

        return view('pages.email.read', [
            'folder' => 'sent',
            'messageCounts' => $this->messageCounts($request->user()),
            'mailIdentity' => $request->user()->email,
            'message' => $message,
            'recipientRecord' => null,
            'readContext' => 'sent',
            'threadMessages' => $this->threadMessagesFor($request->user(), $message),
        ]);
    }

    protected function recipientMailboxQuery(User $user, string $search = '')
    {
        $query = InternalMessageRecipient::query()
            ->with(['message.sender', 'message.recipients.user'])
            ->where('user_id', $user->id);

        if ($search !== '') {
            $query->whereHas('message', function ($messageQuery) use ($search) {
                $messageQuery->where('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('sender', fn ($senderQuery) => $senderQuery->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function threadMessagesFor(User $user, InternalMessage $message): Collection
    {
        $threadKey = $message->threadKey();

        return InternalMessage::query()
            ->with(['sender', 'recipients.user'])
            ->where(function ($query) use ($threadKey) {
                $query->where('id', $threadKey)
                    ->orWhere('thread_root_id', $threadKey);
            })
            ->orderBy('created_at')
            ->get()
            ->filter(fn (InternalMessage $threadMessage) => $this->userCanAccessMessage($user, $threadMessage))
            ->values();
    }

    protected function userCanAccessMessage(User $user, InternalMessage $message): bool
    {
        if ((int) $message->sender_id === (int) $user->id) {
            return true;
        }

        return $message->recipients()->where('user_id', $user->id)->exists();
    }

    protected function messageCounts(User $user): array
    {
        $mailboxQuery = InternalMessageRecipient::query()->where('user_id', $user->id);
        $activeQuery = (clone $mailboxQuery)->whereNull('deleted_at');

        return [
            'inbox' => (clone $activeQuery)->count(),
            'unread' => (clone $activeQuery)->whereNull('read_at')->count(),
            'starred' => (clone $activeQuery)->whereNotNull('starred_at')->count(),
            'archived' => (clone $mailboxQuery)->whereNotNull('deleted_at')->count(),
            'sent' => InternalMessage::query()->where('sender_id', $user->id)->count(),
        ];
    }
}
