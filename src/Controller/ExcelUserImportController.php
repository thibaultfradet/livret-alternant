<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class ExcelUserImportController extends AbstractController
{
    #[Route('/import-user', name: 'app_excel_user_import')]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $excelFile = $request->files->get('excelFile');

            if ($excelFile) {
                try {  
                    $message = 'Fichier importé avec succès !';
                } catch (FileException $e) {
                    $message = 'Erreur lors de l’import du fichier.';
                }

                return $this->render('excel_user_import/index.html.twig', [
                    'message' => $message,
                ]);
            }
        }

        return $this->render('excel_user_import/index.html.twig');
    }
}
