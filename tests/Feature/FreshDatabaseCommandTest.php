<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

test('the fresh command removes stored media directories before running migrate fresh', function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('assets');
    Storage::disk('local')->put('1/file.pdf', 'pdf');
    Storage::disk('public')->put('25/cover.jpg', 'image');
    Storage::disk('local')->put('livewire-tmp/upload.tmp', 'temporary');

    $freshOptions = [];
    Artisan::command('migrate:fresh {--seed} {--force}', function () use (&$freshOptions): int {
        $freshOptions = $this->options();

        return 0;
    });

    $this->artisan('app:fresh', ['--seed' => true])
        ->expectsOutputToContain('Removed 2 media directories.')
        ->assertSuccessful();

    expect(Storage::disk('local')->exists('1'))->toBeFalse()
        ->and(Storage::disk('public')->exists('25'))->toBeFalse()
        ->and(Storage::disk('local')->exists('livewire-tmp/upload.tmp'))->toBeTrue()
        ->and($freshOptions['seed'])->toBeTrue();
});

test('the fresh command clears media library items before rebuilding the database', function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('assets');
    Artisan::command('migrate:fresh {--seed} {--force}', fn (): int => 0);
    $product = Product::factory()->create();
    $product->attachMedia('product.media.images', UploadedFile::fake()->image('photo.jpg'));

    $this->artisan('app:fresh')->assertSuccessful();

    expect(Media::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});
