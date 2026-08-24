<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Platform-wide event feed shown on the admin dashboard's Activity tab.
 */
class ActivityLog extends Model
{
    use HasFactory, Prunable;

    /**
     * How long a log lives before `model:prune` removes it — the admin's
     * own "archive old logs" action already moves anything an admin wants
     * kept out of this table's active-vs-archived scope, so this is a
     * hard floor for the truly stale, not the primary retention control.
     */
    private const PRUNE_AFTER_DAYS = 90;

    protected $table = 'activity_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'title',
        'sub',
        'badge',
        'user_id',
        'user_name',
        'user_role',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Generate id/created_at here instead of relying on Postgres
        // column defaults, so the model behaves the same on sqlite (tests)
        // and Postgres (production).
        static::creating(function (self $log) {
            $log->id ??= (string) Str::uuid();
            $log->created_at ??= now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an event. $type must be one of the values the admin
     * dashboard's Activity filter understands: registration, login,
     * content, system, error.
     *
     * $user is the account the event is primarily about (the student who
     * logged in, the account that was approved, the admin who performed a
     * platform action, etc.) — it drives the Activity tab's role filter
     * and search. Pass null for events with no single relevant account
     * (e.g. platform-wide system events).
     */
    public static function record(string $type, string $title, ?string $sub = null, ?string $badge = null, ?User $user = null): void
    {
        // Best-effort: the admin activity feed is observability, not a
        // guarantee — a logging failure must never block the real action
        // (registration, login, approval) that triggered it.
        try {
            static::create([
                'type' => $type,
                'title' => $title,
                'sub' => $sub,
                'badge' => $badge ?? ucfirst($type),
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ActivityLog::record failed', ['type' => $type, 'title' => $title, 'error' => $e->getMessage()]);
        }
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }

    public function scopeOfRole(Builder $query, ?string $role): Builder
    {
        return $role ? $query->where('user_role', $role) : $query;
    }

    /**
     * Free-text search across the actor's name/email (via title/sub, since
     * those already embed "Name (email)") and the activity's own title/sub.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('sub', 'like', "%{$term}%")
                ->orWhere('user_name', 'like', "%{$term}%");
        });
    }

    public function scopeOnDate(Builder $query, ?string $date): Builder
    {
        return $date ? $query->whereDate('created_at', $date) : $query;
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Rows eligible for the daily `model:prune` scheduled task (see
     * routes/console.php) — anything older than the retention window,
     * archived or not.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(self::PRUNE_AFTER_DAYS));
    }
}
