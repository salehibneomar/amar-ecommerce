<?php

return [
    'media' => [
        'image' => [
            'type' => 'image',
            'collection' => 'image',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => (int) env('CATEGORY_IMAGE_MAX_SIZE_MB', 3) * 1024,
            'disk' => 'public',
            'conversions' => [
                'small' => ['width' => 64, 'height' => 64],
                'regular' => ['width' => 400, 'height' => 400],
                'large' => ['width' => 600, 'height' => 600],
            ],
            'conversion_format' => 'webp',
            'conversion_quality' => 80,
        ],
    ],
];
