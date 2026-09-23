<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'name',
        'mime',
        'size',
        'extracted_text',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    /**
     * Содержимое файла строкой data: для отправки модели.
     *
     * Провайдер принимает картинку либо ссылкой, либо такой строкой.
     * Ссылка не подходит: файлы закрыты и провайдер их не откроет.
     */
    public function asDataUrl(): string
    {
        $binary = Storage::disk($this->disk)->get($this->path);

        return 'data:'.$this->mime.';base64,'.base64_encode($binary);
    }
}
