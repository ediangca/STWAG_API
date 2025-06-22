<?php
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'auth/reset-password'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8100',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'https://stwagapi-production.up.railway.app',
        'https://stwagapi-production.up.railway.app:443',
        'https://stwagapi-production.up.railway.app:41830',
        '*'
        // For mobile apps (Ionic/Cordova/Capacitor), you can allow all origins or use custom schemes.
        // To allow all origins (not recommended for production):
        // '*',

        // Or, for Capacitor/Ionic, you may use the app's custom scheme:
        // 'capacitor://localhost',
        // 'ionic://localhost',
    ], // 👈 Replace with your frontend URL

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' =>  0,

    'supports_credentials' => true, // 👈 Enable support credentials
];
