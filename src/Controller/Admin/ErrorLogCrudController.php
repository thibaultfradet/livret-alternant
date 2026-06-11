<?php

namespace App\Controller\Admin;

use App\Entity\ErrorLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ErrorLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ErrorLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log d\'erreur')
            ->setEntityLabelInPlural('Logs d\'erreurs')
            ->setDefaultSort(['occurredAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        $occurredAt = DateTimeField::new('occurredAt', 'Date')
            ->setFormat('dd/MM/yyyy HH:mm:ss');
        $statusCode = IntegerField::new('statusCode', 'Code');
        $httpMethod = TextField::new('httpMethod', 'Méthode');

        if ($pageName === Crud::PAGE_INDEX) {
            return [
                $occurredAt,
                $statusCode,
                $httpMethod,
                TextField::new('url', 'URL')->setMaxLength(80),
                TextField::new('userEmail', 'Utilisateur')->setMaxLength(40),
                TextField::new('errorMessage', 'Erreur')->setMaxLength(120),
            ];
        }

        return [
            $occurredAt,
            $statusCode,
            $httpMethod,
            TextField::new('url', 'URL complète'),
            TextField::new('userEmail', 'Email utilisateur'),
            IntegerField::new('userId', 'ID utilisateur'),
            TextField::new('userRoles', 'Rôles')
                ->formatValue(static fn ($v) => is_array($v) ? implode(', ', $v) : '-'),
            TextareaField::new('errorMessage', 'Message d\'erreur')
                ->setNumOfRows(4)
                ->setDisabled(true),
            TextareaField::new('requestPayload', 'Payload')
                ->setNumOfRows(6)
                ->setDisabled(true)
                ->formatValue(static fn ($v) => is_array($v) ? json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-'),
            TextareaField::new('stackTrace', 'Stack trace')
                ->setNumOfRows(20)
                ->setDisabled(true),
        ];
    }
}
