<?php

return [
    'media' => [
        'logo' => [
            'type' => 'image',
            'collection' => 'logo',
            'file_prefix' => 'logo',
            'default' => 'assets/logo/logo.png',
            'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
            'mime_types' => ['image/png', 'image/jpeg', 'image/webp'],
            'max_size' => 1024,
            'disk' => 'public',
        ],
        'icon' => [
            'type' => 'image',
            'collection' => 'icon',
            'file_prefix' => 'icon',
            'default' => 'assets/icon/icon.png',
            'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
            'mime_types' => ['image/png', 'image/jpeg', 'image/webp'],
            'max_size' => 512,
            'disk' => 'public',
        ],
        'favicon' => [
            'type' => 'icon',
            'collection' => 'favicon',
            'file_prefix' => 'favicon',
            'default' => 'assets/icon/favicon.ico',
            'extensions' => ['ico', 'png'],
            'mime_types' => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'],
            'max_size' => 256,
            'disk' => 'public',
        ],
    ],
];
