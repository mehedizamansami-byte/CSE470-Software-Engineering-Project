<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * SERVICE layer: turns a Blade view into a downloadable PDF with dompdf
 * (invoices for Feature 6 and style guides for Feature 9).
 */
class PdfService
{
    public function download(string $view, array $data, string $filename): Response
    {
        $cache = storage_path('app/dompdf');
        File::ensureDirectoryExists($cache);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('fontDir', $cache);
        $options->set('fontCache', $cache);
        $options->set('tempDir', $cache);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
