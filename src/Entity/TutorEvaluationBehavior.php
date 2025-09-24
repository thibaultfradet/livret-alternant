<?php

namespace App\Entity;

use App\Repository\TutorEvaluationBehaviorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TutorEvaluationBehaviorRepository::class)]
class TutorEvaluationBehavior
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TutorEvaluation::class, inversedBy: "behaviors")]
    #[ORM\JoinColumn(nullable: false)]
    private ?TutorEvaluation $tutorEvaluation = null;

    #[ORM\ManyToOne(targetEntity: BehaviorCriteria::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?BehaviorCriteria $behaviorCriteria = null;

    #[ORM\ManyToOne(targetEntity: BehaviorLevel::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?BehaviorLevel $behaviorLevel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTutorEvaluation(): ?TutorEvaluation
    {
        return $this->tutorEvaluation;
    }

    public function setTutorEvaluation(?TutorEvaluation $tutorEvaluation): static
    {
        $this->tutorEvaluation = $tutorEvaluation;

        return $this;
    }

    public function getBehaviorCriteria(): ?BehaviorCriteria
    {
        return $this->behaviorCriteria;
    }

    public function setBehaviorCriteria(?BehaviorCriteria $behaviorCriteria): static
    {
        $this->behaviorCriteria = $behaviorCriteria;

        return $this;
    }

    public function getBehaviorLevel(): ?BehaviorLevel
    {
        return $this->behaviorLevel;
    }

    public function setBehaviorLevel(?BehaviorLevel $behaviorLevel): static
    {
        $this->behaviorLevel = $behaviorLevel;

        return $this;
    }
}
