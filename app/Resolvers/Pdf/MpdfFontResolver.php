<?php

declare(strict_types=1);

namespace App\Resolvers\Pdf;

use Mpdf\Language\LanguageToFont;

final class MpdfFontResolver extends LanguageToFont
{
    /**
     * @param  string  $llcc
     * @param  bool  $adobeCJK
     * @return array{0: bool, 1: string}
     */
    public function getLanguageOptions($llcc, $adobeCJK): array
    {
        return in_array(strtolower(explode('-', $llcc)[0]), ['bn', 'ben'], true)
            ? [false, 'hindsiliguri']
            : parent::getLanguageOptions($llcc, $adobeCJK);
    }
}
