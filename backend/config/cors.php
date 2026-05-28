<?php

$allowedOrigins = array_values(array_filter(array_map(
    fn (string $origin): string => rtrim(trim($origin), '/'),
    explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        env('FRONTEND_URL', 'http://localhost:5173') . ',' . env('APP_URL', 'http://localhost:8000')
    ))
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
