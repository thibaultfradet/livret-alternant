<?php

namespace App\Controller;

use App\Security\Voter\EstablishmentVoter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde d'accès par établissement pour les contrôleurs.
 * Conserve l'UX existante (message flash + redirection vers la liste) tout en déléguant
 * la décision d'autorisation à l'EstablishmentVoter.
 */
trait EstablishmentGuardTrait
{
    private function denyIfForeignEstablishment(object $entity, string $redirectRoute, string $message): ?Response
    {
        if (!$this->isGranted(EstablishmentVoter::MANAGE, $entity)) {
            $this->addFlash('error', $message);
            return $this->redirectToRoute($redirectRoute);
        }

        return null;
    }
}
