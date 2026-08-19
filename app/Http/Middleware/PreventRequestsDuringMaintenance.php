<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * Admin routes stay open so the admin who turned maintenance mode on
     * (from their own dashboard) isn't locked out of turning it back off.
     *
     * @var array<int, string>
     */
    protected $except = [
        'admin/*',
    ];
}
