<?php

namespace App\Entity;

use App\Repository\SchoolYearRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchoolYearRepository::class)]
class SchoolYear
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $label = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $endDate = null;

    #[ORM\Column]
    private ?bool $active = null;

    /**
     * @var Collection<int, Classroom>
     */
    #[ORM\OneToMany(targetEntity: Classroom::class, mappedBy: 'schoolYear')]
    private Collection $classrooms;

    /**
     * @var Collection<int, Period>
     */
    #[ORM\OneToMany(targetEntity: Period::class, mappedBy: 'schoolYear')]
    private Collection $periods;

    /**
     * @var Collection<int, TermsAcceptance>
     */
    #[ORM\OneToMany(targetEntity: TermsAcceptance::class, mappedBy: 'schoolYear')]
    private Collection $termsAcceptances;
   

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $disabledAt = null;


    #[ORM\ManyToOne(inversedBy: 'schoolYears')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Establishment $establishment = null;

    public function __construct()
    {
        $this->classrooms = new ArrayCollection();
        $this->periods = new ArrayCollection();
        $this->termsAcceptances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getClassrooms(): Collection
    {
        return $this->classrooms;
    }

    public function addClassroom(Classroom $classroom): static
    {
        if (!$this->classrooms->contains($classroom)) {
            $this->classrooms->add($classroom);
            $classroom->setSchoolYear($this);
        }

        return $this;
    }

    public function removeClassroom(Classroom $classroom): static
    {
        if ($this->classrooms->removeElement($classroom)) {
            if ($classroom->getSchoolYear() === $this) {
                $classroom->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Period>
     */
    public function getPeriods(): Collection
    {
        return $this->periods;
    }

    public function addPeriod(Period $period): static
    {
        if (!$this->periods->contains($period)) {
            $this->periods->add($period);
            $period->setSchoolYear($this);
        }

        return $this;
    }

    public function removePeriod(Period $period): static
    {
        if ($this->periods->removeElement($period)) {
            if ($period->getSchoolYear() === $this) {
                $period->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TermsAcceptance>
     */
    public function getTermsAcceptances(): Collection
    {
        return $this->termsAcceptances;
    }

    public function addTermsAcceptance(TermsAcceptance $termsAcceptance): static
    {
        if (!$this->termsAcceptances->contains($termsAcceptance)) {
            $this->termsAcceptances->add($termsAcceptance);
            $termsAcceptance->setSchoolYear($this);
        }

        return $this;
    }

    public function removeTermsAcceptance(TermsAcceptance $termsAcceptance): static
    {
        if ($this->termsAcceptances->removeElement($termsAcceptance)) {
            if ($termsAcceptance->getSchoolYear() === $this) {
                $termsAcceptance->setSchoolYear(null);
            }
        }

        return $this;
    }

   
    public function getDisabledAt(): ?\DateTime
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?\DateTime $disabledAt): static
    {
        $this->disabledAt = $disabledAt;

        return $this;
    }


    public function getEstablishment(): ?Establishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }
}
