<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authorised admin email addresses
    |--------------------------------------------------------------------------
    |
    | The single source of truth for which email addresses may hold an admin
    | account. Used by the Google sign-in guard and the AdminSeeder. Set
    | ADMIN_EMAILS as a comma-separated list; the fallback is the known
    | project admins.
    |
    */

    'emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_EMAILS', 'tardio@gmail.com,carman@gmail.com,villamor@gmail.com,tamayuza@gmail.com,embanecido@gmail.com')),
    ))),

];
