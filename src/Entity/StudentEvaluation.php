<?php

namespace App\Entity;

use App\Repository\StudentEvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentEvaluationRepository::class)]
class StudentEvaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validationDate = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $remarks = null;

    #[ORM\ManyToOne(inversedBy: 'studentEvaluations')]
    private ?User $student = null;

    #[ORM\ManyToOne(targetEntity: Period::class, inversedBy: 'studentEvaluations')]
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

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(string $remarks): static
    {
        $this->remarks = $remarks;
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
