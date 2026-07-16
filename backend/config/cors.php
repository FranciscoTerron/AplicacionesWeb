<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Solo orígenes exactos del frontend. Se quitaron los comodines
    // 'https://*.vercel.app' y 'http://localhost:*' porque cualquiera puede
    // desplegar un subdominio *.vercel.app y pasaban la validación CORS.
    'allowed_origins' => [
        'https://aplicaciones-web-tienda.vercel.app',
        'http://localhost:3000',
        'http://localhost:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
