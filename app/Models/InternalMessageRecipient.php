<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMessageRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_message_id',
        'user_id',
        'recipient_type',
        'read_at',
        'starred_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'starred_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(InternalMessage::class, 'internal_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAsUnread(): void
    {
        if ($this->read_at) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    public function toggleReadState(): void
    {
        $this->read_at ? $this->markAsUnread() : $this->markAsRead();
    }

    public function toggleStar(): void
    {
        $this->forceFill([
            'starred_at' => $this->starred_at ? null : now(),
        ])->save();
    }

    public function archive(): void
    {
        if (! $this->deleted_at) {
            $this->forceFill(['deleted_at' => now()])->save();
        }
    }
}
