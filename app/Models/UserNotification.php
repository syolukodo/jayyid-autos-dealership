<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-app alert shown in the portal notification bell (`notifications` table).
 *
 * @property int $id
 * @property int $user_id
 * @property NotificationType $type
 * @property string $title
 * @property ?string $body
 * @property ?string $link site-relative path
 * @property bool $is_read
 */
class UserNotification extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'notifications';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'link', 'is_read', 'read_at'];

    protected $attributes = [
        'is_read' => false,
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): void
    {
        $query->where('is_read', false);
    }

    /**
     * "just now", "5m ago", "3d ago", then a plain date after a week.
     */
    public function age(): string
    {
        if ($this->created_at === null) {
            return '';
        }

        $seconds = max(0, now()->getTimestamp() - $this->created_at->getTimestamp());

        return match (true) {
            $seconds < 60 => 'just now',
            $seconds < 3600 => intdiv($seconds, 60).'m ago',
            $seconds < 86400 => intdiv($seconds, 3600).'h ago',
            $seconds < 604800 => intdiv($seconds, 86400).'d ago',
            default => $this->created_at->format('d M Y'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toBellArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'body' => (string) $this->body,
            'link' => $this->link ? url($this->link) : '',
            'is_read' => $this->is_read,
            'time' => $this->age(),
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
        ];
    }
}
