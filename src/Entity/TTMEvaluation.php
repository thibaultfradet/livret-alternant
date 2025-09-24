<?php

namespace App\Entity;

use App\Repository\TTMEvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TTMEvaluationRepository::class)]
class TTMEvaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validationDate = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $remarks = null;

    #[ORM\ManyToOne(inversedBy: 'ttmEvaluationsGiven')]
    private ?User $ttm = null;

    #[ORM\ManyToOne(inversedBy: 'ttmEvaluationsReceived')]
    private ?User $student = null;

    #[ORM\ManyToOne(inversedBy: 'ttmEvaluations')]
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

    public function getTtm(): ?User
    {
        return $this->ttm;
    }

    public function setTtm(?User $ttm): static
    {
        $this->ttm = $ttm;
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
