<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Núcleo de la instalación
    |--------------------------------------------------------------------------
    |
    | Estas capacidades son parte del producto base y no se pueden desactivar.
    | No se persisten como interruptores para evitar instalaciones inválidas.
    |
    */
    'core' => [
        'identity' => 'Identidad',
        'configuration' => 'Configuración',
        'access' => 'Acceso',
        'audit' => 'Auditoría',
        'clients' => 'Clientes base',
    ],

    /*
    | Los módulos heredados comienzan habilitados para conservar el
    | comportamiento de instalaciones existentes. Los módulos todavía no
    | construidos comienzan deshabilitados.
    */
    'available' => [
        'restaurant' => [
            'name' => 'Restaurante',
            'default' => true,
            'depends_on' => [],
        ],
        'inventory' => [
            'name' => 'Inventario',
            'default' => true,
            'depends_on' => ['restaurant'],
        ],
        'reservations' => [
            'name' => 'Reservas',
            'default' => true,
            'depends_on' => ['restaurant'],
        ],
        'delivery' => [
            'name' => 'Delivery',
            'default' => true,
            'depends_on' => ['restaurant'],
        ],
        'reports' => [
            'name' => 'Reportes',
            'default' => true,
            'depends_on' => ['restaurant'],
        ],
        'sunat' => [
            'name' => 'Facturación electrónica SUNAT',
            'default' => true,
            'depends_on' => ['restaurant'],
        ],
        'landing' => [
            'name' => 'Landing administrable',
            'default' => false,
            'depends_on' => [],
        ],
        'lodging' => [
            'name' => 'Alojamiento',
            'default' => false,
            'depends_on' => [],
        ],
        'marketing' => [
            'name' => 'Marketing y fidelización',
            'default' => false,
            'depends_on' => ['landing'],
        ],
    ],
];
