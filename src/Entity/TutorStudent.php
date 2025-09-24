<?php

namespace App\Entity;

use App\Repository\TutorStudentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TutorStudentRepository::class)]
class TutorStudent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeImmutable $dateDebutContract = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeImmutable $dateFinContract = null;

    #[ORM\ManyToOne(inversedBy: 'tutorContracts')]
    private ?User $tutor = null;

    #[ORM\ManyToOne(inversedBy: 'studentContracts')]
    private ?User $student = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebutContract(): ?\DateTimeImmutable
    {
        return $this->dateDebutContract;
    }

    public function setDateDebutContract(\DateTimeImmutable $dateDebutContract): static
    {
        $this->dateDebutContract = $dateDebutContract;
        return $this;
    }

    public function getDateFinContract(): ?\DateTimeImmutable
    {
        return $this->dateFinContract;
    }

    public function setDateFinContract(\DateTimeImmutable $dateFinContract): static
    {
        $this->dateFinContract = $dateFinContract;
        return $this;
    }

    public function getTutor(): ?User
    {
        return $this->tutor;
    }

    public function setTutor(?User $tutor): static
    {
        $this->tutor = $tutor;
        return $this;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;
        return $this;
    }
}
