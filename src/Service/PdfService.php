<?php

// src/Service/PdfService.php
namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans Mono');
        $options->set('isRemoteEnabled', true);

        // Important → définir le chroot à la racine de public
        $options->setChroot(realpath(__DIR__.'/../../public'));

        $this->dompdf = new Dompdf($options);
    }

    public function generatePdf(string $html, string $filename = 'document.pdf'): void
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();
        $this->dompdf->stream($filename, ['Attachment' => false]);
    }
}