<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TutorStudent;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ExcelUserImportController extends AbstractController
{
    #[Route('/import-user', name: 'app_excel_user_import')]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $message = null;

        if ($request->isMethod('POST')) {
            $excelFile = $request->files->get('excelFile');

            if ($excelFile) {
                try {
                    // Load Excel file
                    $spreadsheet = IOFactory::load($excelFile->getPathname());
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();

                    $emptyRowsCount = 0;

                    foreach ($rows as $index => $row) {
                        // Skip header row
                        if ($index === 0) {
                            continue;
                        }

                        // Check if the row is empty
                        if (empty(array_filter($row))) {
                            $emptyRowsCount++;
                            if ($emptyRowsCount >= 3) {
                                break; // Stop after 3 consecutive empty rows
                            }
                            continue;
                        }
                        $emptyRowsCount = 0; // reset empty row counter

                        [$classCode, $studentFullName, $studentPhone, $studentEmail, $companyName, $companyAddress, $tutorFullName, $tutorPhone, $tutorMobile, $tutorEmail, $tutorEmail2, $stageObservation] = $row;

                        // Skip if student already exists
                        $existingStudent = $em->getRepository(User::class)->findOneBy(['email' => $studentEmail]);
                        if ($existingStudent) {
                            continue;
                        }

                        // Handle tutor
                        $existingTutor = $em->getRepository(User::class)->findOneBy(['email' => $tutorEmail]);
                        if (!$existingTutor) {
                            [$tutorFirstName, $tutorLastName] = explode(' ', $tutorFullName, 2);
                            $tutor = new User();
                            $tutor->setFirstName($tutorFirstName);
                            $tutor->setLastName($tutorLastName);
                            $tutor->setEmail($tutorEmail);
                            $tutor->setRoles(['ROLE_TUTOR']);
                            $tutor->setPassword($passwordHasher->hashPassword($tutor, 'temporaryPassword123')); // Temporary password
                            $em->persist($tutor);
                            $em->flush();
                        } else {
                            $tutor = $existingTutor;
                        }

                        // Create student
                        [$studentFirstName, $studentLastName] = explode(' ', $studentFullName, 2);
                        $student = new User();
                        $student->setFirstName($studentFirstName);
                        $student->setLastName($studentLastName);
                        $student->setEmail($studentEmail);
                        $student->setRoles(['ROLE_USER']);
                        $student->setPassword($passwordHasher->hashPassword($student, 'temporaryPassword123'));
                        $student->setAddress($companyAddress);
                        $em->persist($student);
                        $em->flush();

                        // Link student to tutor
                        $tutorStudent = new TutorStudent();
                        $tutorStudent->setStudent($student);
                        $tutorStudent->setTutor($tutor);
                        $tutorStudent->setDateDebutContract(new \DateTime());
                        $tutorStudent->setDateFinContract((new \DateTime())->modify('+6 months'));
                        $em->persist($tutorStudent);
                        $em->flush();
                    }

                    $message = 'Fichier importé avec succès !';
                } catch (FileException $e) {
                    $message = 'Erreur lors de l’import du fichier : ' . $e->getMessage();
                } catch (\Exception $e) {
                    $message = 'Erreur générale : ' . $e->getMessage();
                }
            } else {
                $message = 'Aucun fichier sélectionné.';
            }
        }

        return $this->render('excel_user_import/index.html.twig', [
            'message' => $message,
        ]);
    }
}
