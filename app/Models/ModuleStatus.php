<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

/**
 * Moderation + metadata for a teacher-uploaded module PDF in Supabase
 * Storage. One row per file, keyed by file_name (the storage path).
 */
class ModuleStatus extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'module_status';

    protected $fillable = [
        'file_name',
        'file_url',
        'status',
        'module_title',
        'module_topic',
        'module_desc',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }
}
