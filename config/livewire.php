<?php

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'local'),
        'rules' => ['required', 'file', 'max:'.((int) env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE_MB', 100) * 1024)],
    ],

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#ffffff',
    ],
];
