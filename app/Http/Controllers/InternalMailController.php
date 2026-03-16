<?php

namespace App\Http\Controllers;

use App\Models\InternalMessage;
use App\Models\InternalMessageAttachment;
use App\Models\InternalMessageRecipient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InternalMailController extends Controller
{
    public function inbox(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $label = $this->normalizeLabel($request->query('label'));

        $query = $this->recipientMailboxQuery($user, $search, $label)
            ->whereNull('deleted_at')
            ->whereNull('trashed_at');

        $messages = $query->latest('created_at')->paginate(12)->withQueryString();

        return view('pages.email.inbox', [
            'folder' => 'inbox',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'selectedLabel' => $label,
            'labels' => InternalMessage::labelOptions(),
            'mailIdentity' => $user->email,
        ]);
    }

    public function starred(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $label = $this->normalizeLabel($request->query('label'));

        $messages = $this->recipientMailboxQuery($user, $search, $label)
            ->whereNull('deleted_at')
            ->whereNull('trashed_at')
            ->whereNotNull('starred_at')
            ->latest('starred_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.email.inbox', [
            'folder' => 'starred',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'selectedLabel' => $label,
            'labels' => InternalMessage::labelOptions(),
            'mailIdentity' => $user->email,
        ]);
    }

    public function archived(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $label = $this->normalizeLabel($request->query('label'));

        $messages = $this->recipientMailboxQuery($user, $search, $label)
            ->whereNotNull('deleted_at')
            ->whereNull('trashed_at')
            ->latest('deleted_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.email.inbox', [
            'folder' => 'archived',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'selectedLabel' => $label,
            'labels' => InternalMessage::labelOptions(),
            'mailIdentity' => $user->email,
        ]);
    }

    public function trash(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $label = $this->normalizeLabel($request->query('label'));

        $messages = $this->recipientMailboxQuery($user, $search, $label)
            ->whereNotNull('trashed_at')
            ->latest('trashed_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.email.inbox', [
            'folder' => 'trash',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'selectedLabel' => $label,
            'labels' => InternalMessage::labelOptions(),
            'mailIdentity' => $user->email,
        ]);
    }

    public function drafts(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $label = $this->normalizeLabel($request->query('label'));

        $query = InternalMessage::query()
            ->with('attachments')
            ->where('sender_id', $user->id)
            ->where('is_draft', true);

        if ($label) {
            $query->where('label', $label);
        }

        if ($search !== '') {
            $query->where(function ($draftQuery) use ($search) {
                $draftQuery->where('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $messages = $query->latest('updated_at')->paginate(12)->withQueryString();

        return view('pages.email.drafts', [
            'folder' => 'drafts',
            'messages' => $messages,
            'messageCounts' => $this->messageCounts($user),
            'search' => $search,
            'selectedLabel' => $label,
            'labels' => InternalMessage::labelOptions(),
            'mailIdentity' => $user->email,
        ]);
    }

    public function sent(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $label = $this->normalizeLabel($request->query('label'));

        $query = InternalMessage::query()
            ->with(['recipients.user', 'sender', 'attachments'])
            ->where('sender_id', $user->id)
            ->where('is_draft', false);

        if ($label) {
            $query->where('label', $label);
        }

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
            'selectedLabel' => $label,
            'labels' => InternalMessage::labelOptions(),
            'mailIdentity' => $user->email,
        ]);
    }

    public function compose(Request $request): View
    {
        $user = $request->user();
        $replyMessage = null;
        $draftMessage = null;
        $prefillTo = collect();
        $prefillCc = collect();
        $prefillSubject = '';
        $prefillBody = '';
        $prefillLabel = InternalMessage::LABEL_GENERAL;
        $replyContext = null;

        if ($request->filled('draft')) {
            $draftMessage = InternalMessage::query()
                ->with('attachments')
                ->where('sender_id', $user->id)
                ->where('is_draft', true)
                ->findOrFail((int) $request->integer('draft'));

            $prefillTo = collect($draftMessage->draft_to ?? []);
            $prefillCc = collect($draftMessage->draft_cc ?? []);
            $prefillSubject = (string) $draftMessage->subject;
            $prefillBody = (string) $draftMessage->body;
            $prefillLabel = (string) ($draftMessage->label ?: InternalMessage::LABEL_GENERAL);

            if ($draftMessage->reply_to_message_id) {
                $replyMessage = InternalMessage::query()
                    ->with(['sender', 'recipients.user'])
                    ->find($draftMessage->reply_to_message_id);
                $replyContext = $replyMessage ? 'thread' : null;
            }
        } elseif ($request->filled('reply')) {
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
            $prefillLabel = (string) ($replyMessage->label ?: InternalMessage::LABEL_GENERAL);
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
            'draftMessage' => $draftMessage,
            'prefillTo' => $prefillTo,
            'prefillCc' => $prefillCc,
            'prefillSubject' => $prefillSubject,
            'prefillBody' => $prefillBody,
            'prefillLabel' => $prefillLabel,
            'labels' => InternalMessage::labelOptions(),
            'replyContext' => $replyContext,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $action = $request->input('mail_action', 'send');
        $draft = null;

        if ($request->filled('draft_id')) {
            $draft = InternalMessage::query()
                ->with('attachments')
                ->where('sender_id', $user->id)
                ->where('is_draft', true)
                ->findOrFail((int) $request->integer('draft_id'));
        }

        if ($action === 'draft') {
            $validated = $request->validate([
                'to' => ['nullable', 'array'],
                'to.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
                'cc' => ['nullable', 'array'],
                'cc.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
                'subject' => ['nullable', 'string', 'max:255'],
                'body' => ['nullable', 'string'],
                'label' => ['nullable', Rule::in(array_keys(InternalMessage::labelOptions()))],
                'reply_to_message_id' => ['nullable', 'integer'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file', 'max:10240'],
            ]);

            $replyMessage = $this->resolveReplyMessage($user, $validated['reply_to_message_id'] ?? null);

            $draft = $this->saveDraft(
                $user,
                $draft,
                collect($validated['to'] ?? [])->map(fn ($id) => (int) $id)->values(),
                collect($validated['cc'] ?? [])->map(fn ($id) => (int) $id)->values(),
                (string) ($validated['subject'] ?? ''),
                (string) ($validated['body'] ?? ''),
                (string) ($validated['label'] ?? InternalMessage::LABEL_GENERAL),
                $replyMessage,
            );

            $this->attachUploadedFiles($draft, $request->file('attachments', []));

            return redirect()
                ->route('email.compose', ['draft' => $draft->id])
                ->with('mail_success', 'Draft saved successfully.');
        }

        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'label' => ['nullable', Rule::in(array_keys(InternalMessage::labelOptions()))],
            'reply_to_message_id' => ['nullable', 'integer'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $replyMessage = $this->resolveReplyMessage($user, $validated['reply_to_message_id'] ?? null);
        $toRecipients = collect($validated['to'])->map(fn ($id) => (int) $id)->values();
        $ccRecipients = collect($validated['cc'] ?? [])->map(fn ($id) => (int) $id)->reject(fn ($id) => $toRecipients->contains($id))->values();

        $message = DB::transaction(function () use ($user, $validated, $toRecipients, $ccRecipients, $draft, $replyMessage) {
            $messageAttributes = [
                'sender_id' => $user->id,
                'thread_root_id' => $replyMessage ? ($replyMessage->thread_root_id ?: $replyMessage->id) : null,
                'reply_to_message_id' => $replyMessage?->id,
                'is_draft' => false,
                'label' => (string) ($validated['label'] ?? InternalMessage::LABEL_GENERAL),
                'draft_to' => null,
                'draft_cc' => null,
                'subject' => $validated['subject'],
                'body' => $validated['body'],
            ];

            if ($draft) {
                $draft->update($messageAttributes);
                $draft->recipients()->delete();
                $message = $draft->fresh();
            } else {
                $message = InternalMessage::create($messageAttributes);
            }

            foreach ($toRecipients as $recipientId) {
                $message->recipients()->create([
                    'user_id' => $recipientId,
                    'recipient_type' => 'to',
                ]);
            }

            foreach ($ccRecipients as $recipientId) {
                $message->recipients()->create([
                    'user_id' => $recipientId,
                    'recipient_type' => 'cc',
                ]);
            }

            return $message;
        });

        $this->attachUploadedFiles($message, $request->file('attachments', []));

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
                'label' => $message->label ?: InternalMessage::LABEL_GENERAL,
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

    public function downloadAttachment(Request $request, InternalMessageAttachment $attachment): BinaryFileResponse
    {
        $attachment->loadMissing('message.recipients');

        abort_unless($this->userCanAccessMessage($request->user(), $attachment->message), 403);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);

        return response()->download(
            Storage::disk('local')->path($attachment->storage_path),
            $attachment->original_name
        );
    }

    public function removeDraftAttachment(Request $request, InternalMessage $draft, InternalMessageAttachment $attachment): RedirectResponse
    {
        abort_unless((int) $draft->sender_id === (int) $request->user()->id, 403);
        abort_unless($draft->is_draft, 403);
        abort_unless((int) $attachment->internal_message_id === (int) $draft->id, 404);

        if (Storage::disk('local')->exists($attachment->storage_path)) {
            Storage::disk('local')->delete($attachment->storage_path);
        }

        $attachment->delete();

        return redirect()
            ->route('email.compose', ['draft' => $draft->id])
            ->with('mail_success', 'Attachment removed from draft.');
    }

    public function deleteDraft(Request $request, InternalMessage $draft): RedirectResponse
    {
        abort_unless((int) $draft->sender_id === (int) $request->user()->id, 403);
        abort_unless($draft->is_draft, 403);

        $draft->loadMissing('attachments');

        foreach ($draft->attachments as $attachment) {
            if (Storage::disk('local')->exists($attachment->storage_path)) {
                Storage::disk('local')->delete($attachment->storage_path);
            }
        }

        $draft->delete();

        return redirect()
            ->route('email.drafts')
            ->with('mail_success', 'Draft deleted successfully.');
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

    public function deleteRecipientMessage(Request $request, InternalMessageRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->moveToTrash();

        return redirect()->route('email.trash')->with('mail_success', 'Message moved to trash.');
    }

    public function restoreRecipientMessage(Request $request, InternalMessageRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->restoreFromTrash();

        return redirect()->route('email.trash')->with('mail_success', 'Message restored to your inbox.');
    }

    public function purgeRecipientMessage(Request $request, InternalMessageRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->purge();

        return redirect()->route('email.trash')->with('mail_success', 'Message permanently deleted.');
    }


    public function bulkMailboxAction(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'message_ids' => ['required', 'array', 'min:1'],
            'message_ids.*' => ['integer'],
            'bulk_action' => ['required', Rule::in(['archive', 'trash', 'restore', 'purge'])],
            'folder' => ['nullable', Rule::in(['inbox', 'starred', 'archived', 'trash'])],
        ]);

        $records = InternalMessageRecipient::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $validated['message_ids'])
            ->get();

        if ($records->isEmpty()) {
            return back()->with('mail_success', 'No mailbox messages were selected.');
        }

        foreach ($records as $record) {
            match ($validated['bulk_action']) {
                'archive' => $record->archive(),
                'trash' => $record->moveToTrash(),
                'restore' => $record->restoreFromTrash(),
                'purge' => $record->purge(),
            };
        }

        $redirectRoute = match ($validated['bulk_action']) {
            'restore', 'purge', 'trash' => 'email.trash',
            default => match ($validated['folder'] ?? 'inbox') {
                'starred' => 'email.starred',
                'archived' => 'email.archived',
                default => 'email.inbox',
            },
        };

        $actionLabel = match ($validated['bulk_action']) {
            'archive' => 'archived',
            'trash' => 'moved to trash',
            'restore' => 'restored',
            'purge' => 'deleted permanently',
        };

        return redirect()->route($redirectRoute)->with('mail_success', $records->count().' message(s) '.$actionLabel.'.');
    }

    public function showInbox(Request $request, InternalMessageRecipient $recipient): View
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->load(['message.sender', 'message.recipients.user', 'message.attachments']);
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

        $message->load(['sender', 'recipients.user', 'attachments']);

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

    protected function attachUploadedFiles(InternalMessage $message, array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $uploadedFile) {
            if (! $uploadedFile instanceof UploadedFile) {
                continue;
            }

            $storedName = Str::uuid()->toString().'.'.$uploadedFile->getClientOriginalExtension();
            $storagePath = $uploadedFile->storeAs('internal-mail', $storedName, 'local');

            $message->attachments()->create([
                'original_name' => $uploadedFile->getClientOriginalName(),
                'stored_name' => $storedName,
                'storage_path' => $storagePath,
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => (int) $uploadedFile->getSize(),
            ]);
        }
    }

    protected function saveDraft(User $user, ?InternalMessage $draft, Collection $toRecipients, Collection $ccRecipients, string $subject, string $body, string $label, ?InternalMessage $replyMessage): InternalMessage
    {
        $attributes = [
            'sender_id' => $user->id,
            'thread_root_id' => $replyMessage ? ($replyMessage->thread_root_id ?: $replyMessage->id) : null,
            'reply_to_message_id' => $replyMessage?->id,
            'is_draft' => true,
            'label' => $label,
            'draft_to' => $toRecipients->all(),
            'draft_cc' => $ccRecipients->reject(fn ($id) => $toRecipients->contains($id))->values()->all(),
            'subject' => $subject,
            'body' => $body,
        ];

        if ($draft) {
            $draft->update($attributes);
            return $draft->fresh();
        }

        return InternalMessage::create($attributes);
    }

    protected function resolveReplyMessage(User $user, mixed $replyToMessageId): ?InternalMessage
    {
        if (! $replyToMessageId) {
            return null;
        }

        $replyMessage = InternalMessage::query()->findOrFail((int) $replyToMessageId);
        abort_unless($this->userCanAccessMessage($user, $replyMessage), 403);

        return $replyMessage;
    }

    protected function recipientMailboxQuery(User $user, string $search = '', ?string $label = null)
    {
        $query = InternalMessageRecipient::query()
            ->with(['message.sender', 'message.recipients.user', 'message.attachments'])
            ->where('user_id', $user->id);

        if ($label) {
            $query->whereHas('message', fn ($messageQuery) => $messageQuery->where('label', $label));
        }

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
            ->with(['sender', 'recipients.user', 'attachments'])
            ->where('is_draft', false)
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
        $activeQuery = (clone $mailboxQuery)->whereNull('deleted_at')
            ->whereNull('trashed_at');

        return [
            'inbox' => (clone $activeQuery)->count(),
            'unread' => (clone $activeQuery)->whereNull('read_at')->count(),
            'starred' => (clone $activeQuery)->whereNotNull('starred_at')->count(),
            'archived' => (clone $mailboxQuery)->whereNotNull('deleted_at')->whereNull('trashed_at')->count(),
            'trash' => (clone $mailboxQuery)->whereNotNull('trashed_at')->count(),
            'drafts' => InternalMessage::query()->where('sender_id', $user->id)->where('is_draft', true)->count(),
            'sent' => InternalMessage::query()->where('sender_id', $user->id)->where('is_draft', false)->count(),
        ];
    }

    protected function normalizeLabel(mixed $label): ?string
    {
        $label = is_string($label) ? trim($label) : '';

        return array_key_exists($label, InternalMessage::labelOptions()) ? $label : null;
    }
}




