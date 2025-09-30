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
       // src/Service/PdfService.php
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans Mono');    
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $this->dompdf = new Dompdf($options);

    }

    public function generatePdf(string $html, string $filename = 'document.pdf'): void
    {
        // Load HTML content
        $this->dompdf->loadHtml($html);

        // Set paper size and orientation
        $this->dompdf->setPaper('A4', 'portrait');

        // Render the PDF
        $this->dompdf->render();

        // Output the PDF to browser
        $this->dompdf->stream($filename, ['Attachment' => false]);
    }
}
