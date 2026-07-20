<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform operator two-factor authentication
    |--------------------------------------------------------------------------
    |
    | When true, Fortify 2FA is enabled and platform operators must set up 2FA
    | before using /platform routes. Set PLATFORM_2FA_REQUIRED=true in .env,
    | or comment/remove the line to disable (default: false).
    |
    */

    '2fa_required' => filter_var(env('PLATFORM_2FA_REQUIRED', false), FILTER_VALIDATE_BOOLEAN),

];
