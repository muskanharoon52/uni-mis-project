<?php

declare(strict_types=1);

return [
    'app_name' => 'Examination Portal',
    'base_url' => '',
    'demo_auth' => [
        'examiner' => [
            '6001' => [
                'password' => 'exam123',
                'display_name' => 'Examiner 6001',
            ],
            '6002' => [
                'password' => 'exam123',
                'display_name' => 'Examiner 6002',
            ],
        ],
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'university_mis',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
];
