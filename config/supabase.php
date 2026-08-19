<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase configuration
    |--------------------------------------------------------------------------
    |
    | Store Supabase connection values in the .env file as SUPABASE_URL and
    | SUPABASE_ANON_KEY. The SupabaseService uses these values via
    | config('supabase.url') and config('supabase.key').
    |
    */

    'url' => env('SUPABASE_URL', null),
    'key' => env('SUPABASE_ANON_KEY', null),
];
