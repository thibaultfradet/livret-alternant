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
        $path = $this->getParameter('kernel.project_dir') . '/public/assets/styles/';
        $bootstrap = file_get_contents($path . "bootstrap-5.3.8.min.css");
        $css = file_get_contents($path . "app.css");
        

        $html = '<!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>Exemple PDF</title>
                <style>' . $bootstrap . '</style>
                <style>' . $css . '</style>
                <style>body { font-family: "DejaVu Sans", sans-serif; }</style>
            </head>
            <body>';
        $html .= $this->renderView('extraction/index.html.twig', [
                'controller_name' => 'ExtractionController',
        ]);
        $html .= '</body></html>';
        
        // Generate and stream the PDF
        $pdfService->generatePdf($html, 'extraction.pdf');

        // Return empty response because PDF is already streamed
        return new Response();
    }
}
