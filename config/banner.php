<?php

return [
    'media' => [
        'image' => [
            'type' => 'image',
            'collection' => 'image',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => (int) env('BANNER_IMAGE_MAX_SIZE_MB', 5) * 1024,
            'disk' => 'public',
            'conversions' => [
                'small' => ['width' => 160, 'height' => 60],
                'regular' => ['width' => 1600, 'height' => 600],
            ],
            'conversion_format' => 'webp',
            'conversion_quality' => 85,
        ],
    ],
];
