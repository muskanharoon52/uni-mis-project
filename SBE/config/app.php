<?php

declare(strict_types=1);

return [
    'app_name' => 'SBE Portal',
    'base_url' => '',
    'demo_auth' => [
        'teacher' => [
            '5001' => [
                'password' => 'teacher123',
                'display_name' => 'Teacher 5001',
            ],
            '5002' => [
                'password' => 'teacher123',
                'display_name' => 'Teacher 5002',
            ],
        ],
        'student' => [
            '9001' => [
                'password' => 'student123',
                'display_name' => 'Student 9001',
            ],
            '9002' => [
                'password' => 'student123',
                'display_name' => 'Student 9002',
            ],
        ],
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'university_sbe',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
];
