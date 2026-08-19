<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Platform-wide event feed shown on the admin dashboard's Activity tab.
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'title',
        'sub',
        'badge',
    ];

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

    /**
     * Record an event. $type must be one of the values the admin
     * dashboard's Activity filter understands: registration, login,
     * content, system, error.
     */
    public static function record(string $type, string $title, ?string $sub = null, ?string $badge = null): void
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
            ]);
        } catch (\Throwable $e) {
            Log::warning('ActivityLog::record failed', ['type' => $type, 'title' => $title, 'error' => $e->getMessage()]);
        }
    }
}
