<?php

return [
    'indexing_enabled' => (bool) env('SEO_INDEXING_ENABLED', true),
    'page_noindex_enabled' => (bool) env('SEO_PAGE_NOINDEX_ENABLED', false),
    'description' => env('SEO_DESCRIPTION', ''),
    'image' => env('SEO_IMAGE'),
    'google_site_verification' => env('SEO_GOOGLE_SITE_VERIFICATION'),

    'media' => [
        'image' => [
            'type' => 'image',
            'collection' => 'share-image',
            'file_prefix' => 'share',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => (int) env('SEO_IMAGE_MAX_SIZE_MB', 2) * 1024,
            'conversions' => [
                'small' => ['width' => 240, 'height' => 126],
                'share' => ['width' => 1200, 'height' => 630],
            ],
            'conversion_format' => 'jpg',
            'conversion_quality' => 85,
        ],
    ],
];
