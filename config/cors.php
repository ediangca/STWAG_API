
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'], // Allow these paths for CORS

    'allowed_methods' => ['*'], // Allow all HTTP methods

    'allowed_origins' => ['http://localhost:4200'], // Set your frontend URL here

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // Must be true for Sanctum to allow cookies/sessions
];
