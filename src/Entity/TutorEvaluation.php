<?php

namespace App\Entity;

use App\Repository\TutorEvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TutorEvaluationRepository::class)]
class TutorEvaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validationDate = null;

    #[ORM\Column]
    private array $contents = [];

    #[ORM\ManyToOne(inversedBy: 'tutorEvaluationsGiven')]
    private ?User $tutor = null;

    #[ORM\ManyToOne(inversedBy: 'tutorEvaluationsReceived')]
    private ?User $student = null;

    #[ORM\ManyToOne(inversedBy: 'tutorEvaluations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Period $period = null;


    public function __construct()
    {
        $this->validationDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValidationDate(): ?\DateTimeImmutable
    {
        return $this->validationDate;
    }

    public function setValidationDate(\DateTimeImmutable $validationDate): static
    {
        $this->validationDate = $validationDate;
        return $this;
    }


    public function getContents(): array
    {
        return array_unique($this->contents);
    }

    public function setContents(array $contents): static
    {
        $this->contents = $contents;
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

    public function getPeriod(): ?Period
    {
        return $this->period;
    }

    public function setPeriod(?Period $period): static
    {
        $this->period = $period;

        return $this;
    }
}
