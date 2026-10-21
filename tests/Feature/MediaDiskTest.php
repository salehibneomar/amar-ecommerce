<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('uploads are stored on the disk set by MEDIA_DISK', function (string $disk) {
    config(['filesystems.media' => $disk]);
    Storage::fake($disk);

    $media = Product::factory()->create()->attachMedia('product.media.images', UploadedFile::fake()->image('photo.jpg', 600, 600));

    expect($media->disk)->toBe($disk)
        ->and(Storage::disk($disk)->exists($media->getPathRelativeToRoot()))->toBeTrue()
        ->and(Storage::disk($disk)->exists($media->getPathRelativeToRoot('regular')))->toBeTrue();
})->with(['public', 'assets', 'r2']);

test('a media type can override the default disk', function () {
    config(['filesystems.media' => 'public', 'product.media.images.disk' => 'assets']);
    Storage::fake('public');
    Storage::fake('assets');

    $media = Product::factory()->create()->attachMedia('product.media.images', UploadedFile::fake()->image('photo.jpg'));

    expect($media->disk)->toBe('assets')
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});
