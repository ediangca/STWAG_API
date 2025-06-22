<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // 'api/*', // Uncomment this line to exclude all API routes from CSRF verification
        'api/auth/resetpassword', // Uncomment this line if you have a specific route for password reset in API
        // 'sanctum/csrf-cookie', // Uncomment this line if using Sanctum for SPA authentication
    ];
}
