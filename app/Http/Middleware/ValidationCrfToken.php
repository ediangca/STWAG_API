<?php

namespace  App\Http\Middleware;

/**
 * Alias of VerifyCsrfToken for consistency.
 */
class ValidateCsrfToken extends VerifyCsrfToken
{
    //

    // The ValidateCsrfToken class is an alias for VerifyCsrfToken.
    // It inherits all properties and methods from VerifyCsrfToken.
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // 'api/*', // Uncomment this line to exclude all API routes from CSRF verification
        // 'api/auth/resetpassword', // Uncomment this line if you have a specific route for password reset in API
        'sanctum/csrf-cookie', // Uncomment this line if using Sanctum for SPA authentication
    ];
}
