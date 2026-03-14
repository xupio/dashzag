<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'thread_root_id',
        'reply_to_message_id',
        'subject',
        'body',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function threadRoot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'thread_root_id');
    }

    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(InternalMessageRecipient::class)->latest();
    }

    public function toRecipients(): HasMany
    {
        return $this->hasMany(InternalMessageRecipient::class)->where('recipient_type', 'to');
    }

    public function ccRecipients(): HasMany
    {
        return $this->hasMany(InternalMessageRecipient::class)->where('recipient_type', 'cc');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_message_id')->oldest('created_at');
    }

    public function threadMessages(): HasMany
    {
        return $this->hasMany(self::class, 'thread_root_id')->oldest('created_at');
    }

    public function threadKey(): int
    {
        return $this->thread_root_id ?: $this->id;
    }
}
