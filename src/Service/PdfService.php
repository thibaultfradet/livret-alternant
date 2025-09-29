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
        // Configure Dompdf options
        $options = new Options();
        $options->set('defaultFont', 'Arial'); // Set default font
        $options->set('isRemoteEnabled', true); // Allow loading images from URLs

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
