<?php

use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        foreach (UserRoleEnum::cases() as $role) {
            Role::findOrCreate($role->value);
        }

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function realPdf(int $pages = 4, string $name = 'book.pdf'): UploadedFile
{
    $html = implode('<div style="page-break-after: always;"></div>', array_map(fn (int $page): string => "<p>Page {$page}</p>", range(1, $pages)));

    return UploadedFile::fake()->createWithContent($name, pdfFromHtml($html));
}

function pdfFromHtml(string $html): string
{
    $mpdf = new Mpdf(['tempDir' => storage_path('framework/cache/mpdf')]);
    $mpdf->WriteHTML($html);

    return $mpdf->Output('', Destination::STRING_RETURN);
}

function pdfPageCount(string $contents): int
{
    return preg_match_all('/\/Type\s*\/Page(?!s)\b/', $contents);
}

function skipWithoutPdfLib(): void
{
    $binary = (string) config('book.preview.node');

    if (trim((string) shell_exec('command -v '.escapeshellarg($binary))) === '' || ! is_dir(base_path('node_modules/pdf-lib'))) {
        test()->markTestSkipped('Node or pdf-lib is not installed.');
    }
}
