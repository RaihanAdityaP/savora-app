<?php

/*
|--------------------------------------------------------------------------
| CORS Configuration
|--------------------------------------------------------------------------
*/

return [

    /*
    | Routes yang kena CORS
    | 'api/*' = semua endpoint di routes/api.php
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    | HTTP methods yang diizinkan
    */
    'allowed_methods' => ['*'],

    /*
    | Origins (domain) yang diizinkan memanggil API
    |
    | Untuk development: '*' (semua) — OK
    | Untuk production: ganti dengan domain spesifik
    |   misal: ['https://web.savora.com', 'https://api.savora.com']
    |
    | Flutter mobile tidak perlu di-whitelist di sini karena
    | request dari native app tidak dikirim lewat browser.
    */
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    /*
    | Headers yang diizinkan dikirim client
    */
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | Untuk cookie-based auth (web). Mobile pakai Bearer token, jadi false OK.
    */
    'supports_credentials' => false,

];