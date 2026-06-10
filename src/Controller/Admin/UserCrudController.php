<?php

// src/Controller/Admin/UserCrudController.php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('firstName', 'First Name'),
            TextField::new('lastName', 'Last Name'),
            EmailField::new('email'),

            // Role field
            ChoiceField::new('roles')
                ->setChoices([
                    'Membre de l\'équipe pédagogique' => 'ROLE_TTM',
                    'Prof Principal' => 'ROLE_PT',
                    'Alternant' => 'ROLE_STUDENT',
                    'Tuteur entreprise' => 'ROLE_TUTOR',
                    'Administrateur' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(),

            // Etablissement association
            AssociationField::new('establishment', 'Etablissement')
                ->setCrudController(EstablishmentCrudController::class)
                ->setRequired(true),
        ];
    }

    // Ensure ROLE_USER is always present
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $roles = $entityInstance->getRoles();
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
            }
            $entityInstance->setRoles($roles);

            $entityInstance->setPassword('');
            $entityInstance->setIsApprentissage(true);

        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $roles = $entityInstance->getRoles();
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
            }
            $entityInstance->setRoles($roles);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}