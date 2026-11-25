<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Envíos externos a SUNAT
    |--------------------------------------------------------------------------
    |
    | Deben permanecer desactivados en desarrollo y pruebas. La habilitación
    | exige completar la lista de control documentada de seguridad y SUNAT.
    |
    */
    'send_enabled' => env('SUNAT_SEND_ENABLED', false),
    'mode' => env('SUNAT_MODE', 'disabled'),
    'production_enabled' => env('SUNAT_PRODUCTION_ENABLED', false),
    'max_attempts' => (int) env('SUNAT_MAX_ATTEMPTS', 3),
];
