<?php

return [
    'media' => [
        'images' => [
            'type' => 'image',
            'collection' => 'images',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => (int) env('PRODUCT_IMAGE_MAX_SIZE_MB', 3) * 1024,
            'max_files' => (int) env('PRODUCT_IMAGE_MAX_FILES', 5),
            'conversions' => [
                'small' => ['width' => 96, 'height' => 96],
                'regular' => ['width' => 500, 'height' => 500],
                'large' => ['width' => 1000, 'height' => 1000],
            ],
            'conversion_format' => 'webp',
            'conversion_quality' => 80,
        ],
    ],
];
