<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\JsonResponse;

class SupabaseController extends Controller
{
    public function test(): JsonResponse
    {
        try {
            // Instantiate the client. If the package isn't installed this will throw.
            SupabaseService::client();

            // Return minimal info so this endpoint can be used to verify the connection.
            return response()->json([
                'ok' => true,
                'message' => 'Supabase client instantiated',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Failed to instantiate Supabase client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
