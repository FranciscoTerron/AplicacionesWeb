<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Orígenes exactos del frontend. Se quitó el comodín 'https://*.vercel.app'
    // (cualquiera podía desplegar un subdominio y pasar CORS). El dominio de
    // prod es el alias con sufijo (-rho); se incluyen ambos + localhost.
    'allowed_origins' => [
        'https://aplicaciones-web-tienda.vercel.app',
        'https://aplicaciones-web-tienda-rho.vercel.app',
        'http://localhost:3000',
        'http://localhost:5173',
    ],

    // Aliases y preview deploys del proyecto front (aplicaciones-web-tienda-*.vercel.app).
    // Más acotado que '*.vercel.app': solo matchea subdominios de ESTE proyecto.
    'allowed_origins_patterns' => [
        '#^https://aplicaciones-web-tienda[a-z0-9-]*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
