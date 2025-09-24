<?php

namespace App\Entity;

use App\Repository\TutorEvaluationSkillRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TutorEvaluationSkillRepository::class)]
class TutorEvaluationSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TutorEvaluation::class, inversedBy: "skills")]
    #[ORM\JoinColumn(nullable: false)]
    private ?TutorEvaluation $tutorEvaluation = null;

    #[ORM\ManyToOne(targetEntity: SkillCriteria::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SkillCriteria $skillCriteria = null;

    #[ORM\ManyToOne(targetEntity: SkillLevel::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SkillLevel $skillLevel = null;

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

    public function getSkillCriteria(): ?SkillCriteria
    {
        return $this->skillCriteria;
    }

    public function setSkillCriteria(?SkillCriteria $skillCriteria): static
    {
        $this->skillCriteria = $skillCriteria;

        return $this;
    }

    public function getSkillLevel(): ?SkillLevel
    {
        return $this->skillLevel;
    }

    public function setSkillLevel(?SkillLevel $skillLevel): static
    {
        $this->skillLevel = $skillLevel;

        return $this;
    }
}
