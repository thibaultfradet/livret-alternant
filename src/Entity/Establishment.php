<?php

namespace App\Entity;

use App\Repository\EstablishmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EstablishmentRepository::class)]
class Establishment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $termsConditionsPro = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $termsConditionsAlternance = null;

    #[ORM\Column(nullable: true)]
    private ?array $formationCenter = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, SchoolYear>
     */
    #[ORM\OneToMany(targetEntity: SchoolYear::class, mappedBy: 'Establishment')]
    private Collection $schoolYears;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'Establishment')]
    private Collection $users;

    public function __construct()
    {
        $this->schoolYears = new ArrayCollection();
        $this->users = new ArrayCollection();

        // Automatically set the creation date
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTermsConditionsPro(): ?string
    {
        return $this->termsConditionsPro;
    }

    public function setTermsConditionsPro(?string $termsConditionsPro): static
    {
        $this->termsConditionsPro = $termsConditionsPro;

        return $this;
    }

    public function getTermsConditionsAlternance(): ?string
    {
        return $this->termsConditionsAlternance;
    }

    public function setTermsConditionsAlternance(?string $termsConditionsAlternance): static
    {
        $this->termsConditionsAlternance = $termsConditionsAlternance;

        return $this;
    }


    public function getFormationCenter(): array
    {
        return $this->formationCenter ?? [];
    }

    public function setFormationCenter(array $data): self
    {
        $this->formationCenter = $data;
        return $this;
    }

    public function getGeneralInfo(): string
    {
        return $this->formationCenter['generalInfo'] ?? [];
    }

    public function setGeneralInfo(string $generalInfo): self
    {
        $this->formationCenter['generalInfo'] = $generalInfo;
        return $this;
    }
    

    // Helper methods for subfields
    public function getDirector(): array
    {
        return $this->formationCenter['director'] ?? [];
    }

    public function setDirector(array $director): self
    {
        $this->formationCenter['director'] = $director;
        return $this;
    }

    public function getCampusDirector(): array
    {
        return $this->formationCenter['campusDirector'] ?? [];
    }

    public function setCampusDirector(array $campusDirector): self
    {
        $this->formationCenter['campusDirector'] = $campusDirector;
        return $this;
    }

    public function getAlternanceManager(): array
    {
        return $this->formationCenter['alternanceManager'] ?? [];
    }

    public function setAlternanceManager(array $alternanceManager): self
    {
        $this->formationCenter['alternanceManager'] = $alternanceManager;
        return $this;
    }

    public function getHandicapReferent(): array
    {
        return $this->formationCenter['handicapReferent'] ?? [];
    }

    public function setHandicapReferent(array $handicapReferent): self
    {
        $this->formationCenter['handicapReferent'] = $handicapReferent;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, SchoolYear>
     */
    public function getSchoolYears(): Collection
    {
        return $this->schoolYears;
    }

    public function addSchoolYear(SchoolYear $schoolYear): static
    {
        if (!$this->schoolYears->contains($schoolYear)) {
            $this->schoolYears->add($schoolYear);
            $schoolYear->setEstablishment($this);
        }

        return $this;
    }

    public function removeSchoolYear(SchoolYear $schoolYear): static
    {
        if ($this->schoolYears->removeElement($schoolYear)) {
            // set the owning side to null (unless already changed)
            if ($schoolYear->getEstablishment() === $this) {
                $schoolYear->setEstablishment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setEstablishment($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getEstablishment() === $this) {
                $user->setEstablishment(null);
            }
        }

        return $this;
    }
}
