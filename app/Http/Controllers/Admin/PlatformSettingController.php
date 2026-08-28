<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformSettingController extends Controller
{
    /**
     * Keys the admin Settings tab is allowed to write. Anything else is
     * ignored so a stray/edited request can't seed arbitrary keys.
     *
     * @var list<string>
     */
    private const WRITABLE_KEYS = [
        'platform_name',
        'platform_desc',
        'notif_registration',
        'notif_content',
        'notif_errors',
        'notif_weekly',
    ];

    public function index(): JsonResponse
    {
        return response()->json([
            'settings' => PlatformSetting::all()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            if (! in_array($key, self::WRITABLE_KEYS, true)) {
                continue;
            }

            PlatformSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return response()->json(['saved' => true]);
    }
}
