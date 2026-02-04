<?php

namespace App\Entity;

use App\Repository\TutorEvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(mappedBy: 'tutorEvaluation', targetEntity: TutorEvaluationSkill::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $skillEvaluation;

    #[ORM\OneToMany(mappedBy: 'tutorEvaluation', targetEntity: TutorEvaluationBehavior::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $behaviorEvaluation;

    public function __construct()
    {
        $this->validationDate = new \DateTimeImmutable();
        $this->skillEvaluation = new ArrayCollection();
        $this->behaviorEvaluation = new ArrayCollection();
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

    public function getReferenceHash(): string
    {
        $email = $this->tutor?->getEmail() ?? '';
        return strtoupper(substr(hash('sha256', $email . ':' . $this->id), 0, 8));
    }

    // === SkillEvaluation ===
    public function getSkillEvaluation(): Collection
    {
        return $this->skillEvaluation;
    }

    public function addSkillEvaluation(TutorEvaluationSkill $skill): self
    {
        if (!$this->skillEvaluation->contains($skill)) {
            $this->skillEvaluation->add($skill);
            $skill->setTutorEvaluation($this);
        }

        return $this;
    }

    public function removeSkillEvaluation(TutorEvaluationSkill $skill): self
    {
        if ($this->skillEvaluation->removeElement($skill)) {
            if ($skill->getTutorEvaluation() === $this) {
                $skill->setTutorEvaluation(null);
            }
        }

        return $this;
    }

    // === BehaviorEvaluation ===
    public function getBehaviorEvaluation(): Collection
    {
        return $this->behaviorEvaluation;
    }

    public function addBehaviorEvaluation(TutorEvaluationBehavior $behavior): self
    {
        if (!$this->behaviorEvaluation->contains($behavior)) {
            $this->behaviorEvaluation->add($behavior);
            $behavior->setTutorEvaluation($this);
        }

        return $this;
    }

    public function removeBehaviorEvaluation(TutorEvaluationBehavior $behavior): self
    {
        if ($this->behaviorEvaluation->removeElement($behavior)) {
            if ($behavior->getTutorEvaluation() === $this) {
                $behavior->setTutorEvaluation(null);
            }
        }

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


    // content field replacement 
    public function getStrengths(): ?string
    {
        return $this->contents[0] ?? null;
    }

    public function setStrengths(?string $strengths): self
    {
        $this->contents[0] = $strengths;
        return $this;
    }

    public function getWeaknesses(): ?string
    {
        return $this->contents[1] ?? null;
    }

    public function setWeaknesses(?string $weaknesses): self
    {
        $this->contents[1] = $weaknesses;
        return $this;
    }

    public function getGoals(): ?string
    {
        return $this->contents[2] ?? null;
    }

    public function setGoals(?string $goals): self
    {
        $this->contents[2] = $goals;
        return $this;
    }

    public function getRemarks(): ?string
    {
        return $this->contents[3] ?? null;
    }

    public function setRemarks(?string $remarks): self
    {
        $this->contents[3] = $remarks;
        return $this;
    }
}
