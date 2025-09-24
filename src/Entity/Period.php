<?php

namespace App\Entity;

use App\Repository\PeriodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeriodRepository::class)]
class Period
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $periodNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'periods')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SchoolYear $schoolYear = null;


    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $disabledAt = null;



    /**
     * @var Collection<int, TutorEvaluation>
     */
    #[ORM\OneToMany(targetEntity: TutorEvaluation::class, mappedBy: 'period')]
    private Collection $tutorEvaluations;

    /**
     * @var Collection<int, StudentEvaluation>
     */
    #[ORM\OneToMany(targetEntity: StudentEvaluation::class, mappedBy: 'period')]
    private Collection $studentEvaluations;

    /**
     * @var Collection<int, TTMEvaluation>
     */
    #[ORM\OneToMany(targetEntity: TTMEvaluation::class, mappedBy: 'period')]
    private Collection $ttmEvaluations;

    public function __construct()
    {
        $this->tutorEvaluations = new ArrayCollection();
        $this->studentEvaluations = new ArrayCollection();
        $this->ttmEvaluations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriodNumber(): ?int
    {
        return $this->periodNumber;
    }

    public function setPeriodNumber(int $periodNumber): static
    {
        $this->periodNumber = $periodNumber;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getDisabledAt(): ?\DateTimeInterface
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?\DateTimeInterface $disabledAt): static
    {
        $this->disabledAt = $disabledAt;
        return $this;
    }
    /**
     * @return Collection<int, TutorEvaluation>
     */
    public function getTutorEvaluations(): Collection
    {
        return $this->tutorEvaluations;
    }

    public function addTutorEvaluation(TutorEvaluation $tutorEvaluation): static
    {
        if (!$this->tutorEvaluations->contains($tutorEvaluation)) {
            $this->tutorEvaluations->add($tutorEvaluation);
            $tutorEvaluation->setPeriod($this);
        }
        return $this;
    }

    public function removeTutorEvaluation(TutorEvaluation $tutorEvaluation): static
    {
        if ($this->tutorEvaluations->removeElement($tutorEvaluation)) {
            if ($tutorEvaluation->getPeriod() === $this) {
                $tutorEvaluation->setPeriod(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, StudentEvaluation>
     */
    public function getStudentEvaluations(): Collection
    {
        return $this->studentEvaluations;
    }

    public function addStudentEvaluation(StudentEvaluation $studentEvaluation): static
    {
        if (!$this->studentEvaluations->contains($studentEvaluation)) {
            $this->studentEvaluations->add($studentEvaluation);
            $studentEvaluation->setPeriod($this);
        }
        return $this;
    }

    public function removeStudentEvaluation(StudentEvaluation $studentEvaluation): static
    {
        if ($this->studentEvaluations->removeElement($studentEvaluation)) {
            if ($studentEvaluation->getPeriod() === $this) {
                $studentEvaluation->setPeriod(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, TTMEvaluation>
     */
    public function getTtmEvaluations(): Collection
    {
        return $this->ttmEvaluations;
    }

    public function addTtmEvaluation(TTMEvaluation $ttmEvaluation): static
    {
        if (!$this->ttmEvaluations->contains($ttmEvaluation)) {
            $this->ttmEvaluations->add($ttmEvaluation);
            $ttmEvaluation->setPeriod($this);
        }
        return $this;
    }

    public function removeTtmEvaluation(TTMEvaluation $ttmEvaluation): static
    {
        if ($this->ttmEvaluations->removeElement($ttmEvaluation)) {
            if ($ttmEvaluation->getPeriod() === $this) {
                $ttmEvaluation->setPeriod(null);
            }
        }
        return $this;
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
