<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    /**
     * Отметка в ленте: смена модели и прочие события.
     *
     * Модели не отправляется, показывается тонкой строкой между
     * сообщениями, чтобы было видно, где разговор сменил собеседника.
     */
    public const ROLE_SYSTEM = 'system';

    protected $fillable = [
        'role',
        'content',
        'model',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
