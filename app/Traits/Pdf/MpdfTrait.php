<?php

namespace App\Traits\Pdf;

use App\Resolvers\Pdf\MpdfFontResolver;
use Illuminate\Contracts\View\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

trait MpdfTrait
{
    private function downloadMpdf(View $view, string $filename): Response
    {
        return response($this->renderMpdf($view), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
        ]);
    }

    private function renderMpdf(View $view): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => storage_path('framework/cache/mpdf'),
            'fontDir' => [...(new ConfigVariables)->getDefaults()['fontDir'], resource_path('fonts/hind-siliguri')],
            'fontdata' => (new FontVariables)->getDefaults()['fontdata'] + [
                'hindsiliguri' => [
                    'R' => 'HindSiliguri-Regular.ttf',
                    'B' => 'HindSiliguri-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'hindsiliguri',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
            'languageToFont' => new MpdfFontResolver,
        ]);

        $mpdf->WriteHTML($view->render());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
