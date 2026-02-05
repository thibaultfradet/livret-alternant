<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\User;
use App\Entity\TutorStudent;
use App\Repository\ClassroomRepository;
use App\Repository\DiplomaRepository;
use App\Repository\SchoolYearRepository;
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
    public function index(Request $request , SchoolYearRepository $schoolYearRepo,  DiplomaRepository $diplomaRepo , ClassroomRepository $classroomRepo , EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $message = null;

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $activeSchoolYear = $schoolYearRepo->findActiveByEstablishment($currentUser->getEstablishment());

        if (!$activeSchoolYear) {
            $this->addFlash('danger', "Aucune année scolaire active n'a été trouvée. Veuillez créer et activer une année scolaire avant d'importer des utilisateurs, car les classes dépendent de l'année scolaire.");
            return $this->redirectToRoute('app_home');
        }

        $diplomas = $diplomaRepo->findBy([
            'establishment' => $currentUser->getEstablishment(),
            'disabledAt' => null,
        ]);

        if ($request->isMethod('POST')) {
            $excelFile = $request->files->get('excelFile');

            if ($excelFile) {
                try {
                    // Load Excel file
                    $spreadsheet = IOFactory::load($excelFile->getPathname());
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();

                    // Find header row containing "Stagiaire : Code classe" in column A
                    $headerRowIndex = null;
                    $maxSearchRows = min(10, count($rows));
                    for ($i = 0; $i < $maxSearchRows; $i++) {
                        if (isset($rows[$i][0]) && trim($rows[$i][0]) === 'Stagiaire : Code classe') {
                            $headerRowIndex = $i;
                            break;
                        }
                    }

                    if ($headerRowIndex === null) {
                        $this->addFlash('danger', "En-tête \"Stagiaire : Code classe\" introuvable dans les 10 premières lignes de la colonne A. Vérifiez le format du fichier.");
                        return $this->redirectToRoute('app_excel_user_import');
                    }

                    $emptyRowsCount = 0;

                    foreach ($rows as $index => $row) {
                        // Skip rows up to and including the header
                        if ($index <= $headerRowIndex) {
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

                        $diploma = $diplomaRepo->findOneBy(['code' => $classCode]);
                        if (!$diploma) {
                            $this->addFlash('warning', "Classe avec le code $classCode non trouvée pour l'alternant $studentFullName.");
                            continue;
                        }

                        // Try to find the classroom
                        $classroom = $classroomRepo->findOneBy([
                            'diploma' => $diploma,
                            'schoolYear' => $activeSchoolYear
                        ]);

                        // If classroom doesn't exist, create it
                        if (!$classroom) {
                            $classroom = new Classroom();
                            $classroom->setDiploma($diploma);
                            $classroom->setSchoolYear($activeSchoolYear);

                            // Set terms && formation center from the current user's establishment
                            /** @var \App\Entity\User $user */
                            $user = $this->getUser();
                            if ($user && $user->getEstablishment()) {
                                $establishment = $user->getEstablishment();
                                $classroom->setFormationCenter($establishment->getFormationCenter());
                                $classroom->setTermsConditionsPro($establishment->getTermsConditionsPro() ?? '');
                                $classroom->setTermsConditionsApprentissage($establishment->getTermsConditionsApprentissage() ?? '');
                            }

                            $em->persist($classroom);
                            $em->flush();
                        }

                        // Check if student already exists
                        $existingStudent = $em->getRepository(User::class)->findOneBy([
                            'email' => $studentEmail,
                        ]);

                        if ($existingStudent) {
                            // Reactivate student if previously disabled
                            if ($existingStudent->getDisabledAt() !== null) {
                                $existingStudent->setDisabledAt(null);
                                $em->persist($existingStudent);
                                $em->flush();
                            }

                            // Skip creation since student already exists
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
                            $tutor->setCompanyName($companyName);
                            $tutor->setCompanyAddress($companyAddress);
                            if (!empty($tutorPhone)) {
                                $tutor->setPhone($tutorPhone);
                            } elseif (!empty($tutorMobile)) {
                                $tutor->setPhone($tutorMobile);
                            }
                            $em->persist($tutor);
                            $em->flush();
                        } else {
                            // Reactivate tutor if previously disabled
                            if ($existingTutor->getDisabledAt() !== null) {
                                $existingTutor->setDisabledAt(null);
                                $em->persist($existingTutor);
                                $em->flush();
                            }

                            $tutor = $existingTutor;
                        }

                        // Create student
                        [$studentFirstName, $studentLastName] = explode(' ', $studentFullName, 2);
                        $student = new User();
                        $student->setFirstName($studentFirstName);
                        $student->setLastName($studentLastName);
                        $student->setEmail($studentEmail);
                        $student->setRoles(['ROLE_STUDENT']);
                        $student->setPassword($passwordHasher->hashPassword($student, 'temporaryPassword123'));
                        $student->setClassroom($classroom);
                        $student->setEstablishment($currentUser->getEstablishment());
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

                    $this->addFlash('success', 'Utilisateurs créer avec succès !');
                    return $this->redirectToRoute('app_home');
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
            'activeSchoolYear' => $activeSchoolYear,
            'diplomas' => $diplomas,
        ]);
    }
}
