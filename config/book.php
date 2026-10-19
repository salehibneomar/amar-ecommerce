<?php

return [
    'type' => env('BOOK_STOCK_TYPE', 'both'),

    'preview' => [
        'pages' => (int) env('BOOK_PREVIEW_PAGES', 5),
        'ghostscript' => env('GHOSTSCRIPT_BINARY', 'gs'),
        'node' => env('NODE_BINARY', 'node'),
    ],

    'media' => [
        'file' => [
            'type' => 'file',
            'collection' => 'file',
            'extensions' => ['pdf'],
            'mime_types' => ['application/pdf'],
            'max_size' => (int) env('BOOK_FILE_MAX_SIZE_MB', 100) * 1024,
            'disk' => 'local',
        ],
        'preview' => [
            'type' => 'file',
            'collection' => 'preview',
            'extensions' => ['pdf'],
            'mime_types' => ['application/pdf'],
            'max_size' => (int) env('BOOK_FILE_MAX_SIZE_MB', 100) * 1024,
            'disk' => 'local',
        ],
        'cover' => [
            'type' => 'image',
            'collection' => 'cover',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => (int) env('BOOK_COVER_MAX_SIZE_MB', 3) * 1024,
            'disk' => 'public',
            'conversions' => [
                'small' => ['width' => 64, 'height' => 96],
                'regular' => ['width' => 300, 'height' => 450],
                'large' => ['width' => 800, 'height' => 1200],
            ],
            'conversion_format' => 'webp',
            'conversion_quality' => 85,
        ],
    ],
];
