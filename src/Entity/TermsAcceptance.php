<?php

namespace App\Entity;

use App\Repository\TermsAcceptanceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TermsAcceptanceRepository::class)]
class TermsAcceptance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validationDate = null;

    #[ORM\ManyToOne(inversedBy: 'termsAcceptances')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'termsAcceptances')]
    private ?SchoolYear $schoolYear = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getReferenceHash(): string
    {
        $email = $this->user?->getEmail() ?? '';
        return strtoupper(substr(hash('sha256', $email . ':' . $this->id), 0, 8));
    }

    public function getSchoolYear(): ?SchoolYear
    {
        return $this->schoolYear;
    }

    public function setSchoolYear(?SchoolYear $schoolYear): static
    {
        $this->schoolYear = $schoolYear;

        return $this;
    }
}
