<?php

// src/Service/PdfService.php
namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private Options $options;

    public function __construct()
    {
        $this->options = new Options();
        $this->options->set('defaultFont', 'DejaVu Sans Mono');
        $this->options->set('isRemoteEnabled', true);

        // Important → définir le chroot à la racine de public
        $this->options->setChroot(realpath(__DIR__.'/../../public'));
    }

    /**
     * Create a new Dompdf instance with the shared options
     */
    private function createDompdf(): Dompdf
    {
        return new Dompdf($this->options);
    }

    public function generatePdf(string $html, string $filename = 'document.pdf'): void
    {
        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => false]);
    }

    /**
     * Generate a PDF and save it to a file path
     */
    public function generatePdfToFile(string $html, string $filePath): void
    {
        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($filePath, $dompdf->output());
    }
}