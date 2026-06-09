<?php

namespace App\Models;

use Database\Factories\VisualEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'author_id', 'title', 'description', 'type', 'media_path', 'media_file_name', 'media_mime_type', 'media_size', 'thumbnail_path', 'duration', 'visibility', 'published_at'])]
class VisualEntry extends Model
{
    /** @use HasFactory<VisualEntryFactory> */
    use HasFactory;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_INTERNAL = 'internal';

    public const TYPES = [
        'video_demo' => 'Video demo',
        'annotated_capture' => 'Captura comentada',
        'audio_explanation' => 'Audio explicativo',
        'progress_note' => 'Nota de avance',
        'before_after' => 'Antes/despues',
        'test_evidence' => 'Evidencia de prueba',
        'blocker' => 'Bloqueo explicado',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublic(Builder $query): void
    {
        $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->media_mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->media_mime_type, 'video/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->media_mime_type, 'audio/');
    }
}
