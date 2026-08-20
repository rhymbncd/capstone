<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    /**
     * Whether the site is currently in maintenance mode, so the admin
     * dashboard's toggle reflects the real state on load rather than a
     * stale settings flag.
     */
    public function status(): JsonResponse
    {
        return response()->json(['down' => app()->isDownForMaintenance()]);
    }

    /**
     * Turn maintenance mode on or off. The admin portal itself stays
     * reachable while it's on (see App\Http\Middleware\PreventRequestsDuringMaintenance),
     * so the admin who enabled it can always turn it back off here.
     */
    public function toggle(Request $request): JsonResponse
    {
        $enable = $request->boolean('enable');

        /** @var User $admin */
        $admin = Auth::user();

        if ($enable) {
            Artisan::call('down');
            ActivityLog::record('system', 'Maintenance Mode Enabled', 'The site was put into maintenance mode by an admin', user: $admin);
        } else {
            Artisan::call('up');
            ActivityLog::record('system', 'Maintenance Mode Disabled', 'The site was taken out of maintenance mode by an admin', user: $admin);
        }

        return response()->json(['down' => app()->isDownForMaintenance()]);
    }
}
