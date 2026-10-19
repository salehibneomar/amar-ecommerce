<?php

return [
    'media' => [
        'avatar' => [
            'type' => 'image',
            'collection' => 'avatar',
            'file_prefix' => 'avatar',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => (int) env('USER_AVATAR_MAX_SIZE_MB', 2) * 1024,
            'disk' => 'public',
            'conversions' => [
                'small' => ['width' => 64, 'height' => 64],
                'medium' => ['width' => 256, 'height' => 256],
            ],
            'conversion_format' => 'webp',
            'conversion_quality' => 85,
        ],
    ],
];
