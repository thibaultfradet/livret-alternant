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

    #[ORM\Column(type: Types::TEXT)]
    private ?string $strengths = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $weaknesses = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $goals = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $remarks = null;

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

    public function getStrengths(): ?string
    {
        return $this->strengths;
    }

    public function setStrengths(string $strengths): static
    {
        $this->strengths = $strengths;
        return $this;
    }

    public function getWeaknesses(): ?string
    {
        return $this->weaknesses;
    }

    public function setWeaknesses(string $weaknesses): static
    {
        $this->weaknesses = $weaknesses;
        return $this;
    }

    public function getGoals(): ?string
    {
        return $this->goals;
    }

    public function setGoals(string $goals): static
    {
        $this->goals = $goals;
        return $this;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(string $remarks): static
    {
        $this->remarks = $remarks;
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
