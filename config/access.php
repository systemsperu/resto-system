<?php

return [
    'roles' => [
        'admin' => ['*'],
        'cashier' => [
            'cash-register.manage',
            'pos.manage',
            'pos.discount',
            'pos.checkout',
            'kitchen.view',
            'reservations.manage',
            'sales.view',
            'expenses.manage',
            'delivery.manage',
        ],
        'waiter' => [
            'pos.manage',
            'kitchen.view',
            'reservations.manage',
        ],
        'kitchen' => [
            'kitchen.view',
            'kitchen.manage',
        ],
        'reception' => [
            'lodging.view',
            'lodging.reservations.manage',
            'lodging.checkin.manage',
            'lodging.payments.manage',
            'lodging.guests.manage',
        ],
        'cleaning' => [
            'lodging.view',
            'lodging.cleaning.manage',
        ],
    ],
];
