<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\SchoolYear;
use App\Entity\TermsAcceptance;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class UserCheckSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router,
        private EntityManagerInterface $em
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        // Ignorer certaines routes pour éviter les boucles
        $excludedPaths = ['/login', '/logout', '/terms-acceptance/validation'];
        if (in_array($path, $excludedPaths)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user || $user === 'anon.' || !$user instanceof User) {
            return;
        }

        if (\in_array('ROLE_PT', $user->getRoles(), true)) {
            return;
        }

        $establishment = $user->getEstablishment();

        if (!$establishment) {
            // Tutor: check CGU acceptance for each active school year linked via contracts
            $seenYearIds = [];
            foreach ($user->getTutorContracts() as $contract) {
                $schoolYear = $contract->getStudent()?->getClassroom()?->getSchoolYear();
                if (!$schoolYear || !$schoolYear->isActive()) {
                    continue;
                }
                if (\in_array($schoolYear->getId(), $seenYearIds, true)) {
                    continue;
                }
                $seenYearIds[] = $schoolYear->getId();

                $terms = $this->em->getRepository(TermsAcceptance::class)
                    ->findOneBy(['user' => $user, 'schoolYear' => $schoolYear]);
                if (!$terms || !$terms->getValidationDate()) {
                    $event->setResponse(new RedirectResponse(
                        $this->router->generate('app_terms_acceptance_validation')
                    ));
                    return;
                }
            }
            return;
        }

        $year = $this->em->getRepository(SchoolYear::class)->findActiveByEstablishment($establishment);
        if (!$year) {
            return;
        }
        $terms = $this->em->getRepository(TermsAcceptance::class)
            ->findOneBy([
                'user' => $user,
                'schoolYear' => $year
            ]);
        if (!$terms || !$terms->getValidationDate()) {
            $redirectUrl = $this->router->generate('app_terms_acceptance_validation');
            $event->setResponse(new RedirectResponse($redirectUrl));
        }
    }




    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }
}
