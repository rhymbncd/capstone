<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A simple key/value store for platform-wide settings, managed from the
 * admin dashboard's Settings tab (Platform Info, notification prefs, etc).
 */
class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Read a single setting's value, or $default when it hasn't been set.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }
}
