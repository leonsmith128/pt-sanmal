<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
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

function something()
{
    // ..
}

/**
 * Membaca isi workbook XLSX dari response unduhan tanpa bergantung pada API pembaca spreadsheet.
 *
 * @return array{sheets: list<string>, text: string, xml: string, media: list<string>}
 */
function xlsxContents(Illuminate\Testing\TestResponse $response): array
{
    $response->assertOk();

    $zip = new ZipArchive;
    expect($zip->open($response->baseResponse->getFile()->getPathname()))->toBeTrue();

    preg_match_all('/<sheet\b[^>]*\bname="([^"]*)"/', (string) $zip->getFromName('xl/workbook.xml'), $names);

    $xml = '';
    $media = [];

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entry = (string) $zip->getNameIndex($index);

        if (str_starts_with($entry, 'xl/worksheets/sheet')) {
            $xml .= (string) $zip->getFromIndex($index);
        }

        if (str_starts_with($entry, 'xl/media/')) {
            $media[] = $entry;
        }
    }

    preg_match_all('/<t\b[^>]*>(.*?)<\/t>/s', (string) $zip->getFromName('xl/sharedStrings.xml').$xml, $strings);
    $zip->close();

    $decode = fn (string $value): string => html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');

    return [
        'sheets' => array_map($decode, $names[1]),
        'text' => $decode(implode("\n", $strings[1])),
        'xml' => $xml,
        'media' => $media,
    ];
}
