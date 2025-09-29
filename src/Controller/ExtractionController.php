<?php

namespace App\Controller;

use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExtractionController extends AbstractController
{
    #[Route('/extraction', name: 'app_extraction')]
    public function index(PdfService $pdfService): Response
    {
        $html = null;
        
        // adding bootstrap
        $cssFile = $this->getParameter('kernel.project_dir') . '/public/assets/styles/bootstrap-5.3.8.min.css';
        $css = file_get_contents($cssFile);
        $html = '<style>' . $css . '</style>';

        // Render the Twig template as HTML
        $html .= $this->renderView('extraction/index.html.twig', [
            'controller_name' => 'ExtractionController',
        ]);
        
        // Generate and stream the PDF
        $pdfService->generatePdf($html, 'extraction.pdf');

        // Return empty response because PDF is already streamed
        return new Response();
    }
}
