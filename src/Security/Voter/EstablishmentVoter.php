<?php

namespace App\Security\Voter;

use App\Entity\BehaviorCriteria;
use App\Entity\BehaviorLevel;
use App\Entity\Classroom;
use App\Entity\Diploma;
use App\Entity\Establishment;
use App\Entity\Period;
use App\Entity\SchoolYear;
use App\Entity\SkillCriteria;
use App\Entity\SkillGroup;
use App\Entity\SkillLevel;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Autorise un utilisateur à gérer une entité uniquement si elle appartient à son établissement.
 * Centralise la logique « comment atteindre l'établissement propriétaire » pour chaque type
 * d'entité, auparavant dupliquée dans chaque contrôleur.
 */
class EstablishmentVoter extends Voter
{
    public const MANAGE = 'MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE && $this->resolveEstablishment($subject) !== false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $owner = $this->resolveEstablishment($subject);

        // Type non géré (false) ou entité sans établissement (null) : on refuse (fail-closed).
        if (!$owner instanceof Establishment) {
            return false;
        }

        return $owner === $user->getEstablishment();
    }

    /**
     * Retourne l'établissement propriétaire de l'entité, null s'il est introuvable,
     * ou false si le type n'est pas géré par ce voter.
     */
    private function resolveEstablishment(mixed $subject): Establishment|null|false
    {
        return match (true) {
            $subject instanceof Establishment   => $subject,
            $subject instanceof User             => $subject->getEstablishment(),
            $subject instanceof SchoolYear       => $subject->getEstablishment(),
            $subject instanceof Diploma          => $subject->getEstablishment(),
            $subject instanceof BehaviorCriteria => $subject->getEstablishment(),
            $subject instanceof SkillLevel       => $subject->getEstablishment(),
            $subject instanceof Classroom        => $subject->getSchoolYear()?->getEstablishment(),
            $subject instanceof Period           => $subject->getSchoolYear()?->getEstablishment(),
            $subject instanceof SkillGroup       => $subject->getDiploma()?->getEstablishment(),
            $subject instanceof SkillCriteria    => $subject->getSkillGroup()?->getDiploma()?->getEstablishment(),
            $subject instanceof BehaviorLevel    => $subject->getBehaviorCriteria()?->getEstablishment(),
            default                              => false,
        };
    }
}
