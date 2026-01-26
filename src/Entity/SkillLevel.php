<?php

namespace App\Entity;

use App\Repository\SkillLevelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillLevelRepository::class)]
class SkillLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\OneToMany(mappedBy: "skillLevel", targetEntity: TutorEvaluationSkill::class)]
    private Collection $tutorEvaluationSkills;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $disabledAt = null;

    #[ORM\Column(length: 15)]
    private ?string $color = null;

    #[ORM\ManyToOne(inversedBy: 'skillLevels')]
    private ?Establishment $establishment = null;

    #[ORM\Column]
    private ?int $order_index = null;

    public function __construct()
    {
        $this->tutorEvaluationSkills = new ArrayCollection();
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

    public function getDisabledAt(): ?\DateTime
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?\DateTime $disabledAt): static
    {
        $this->disabledAt = $disabledAt;

        return $this;
    }

    /**
     * @return Collection<int, TutorEvaluationSkill>
     */
    public function getTutorEvaluationSkills(): Collection
    {
        return $this->tutorEvaluationSkills;
    }

    public function addTutorEvaluationSkill(TutorEvaluationSkill $tutorEvaluationSkill): static
    {
        if (!$this->tutorEvaluationSkills->contains($tutorEvaluationSkill)) {
            $this->tutorEvaluationSkills->add($tutorEvaluationSkill);
            $tutorEvaluationSkill->setSkillLevel($this);
        }

        return $this;
    }

    public function removeTutorEvaluationSkill(TutorEvaluationSkill $tutorEvaluationSkill): static
    {
        if ($this->tutorEvaluationSkills->removeElement($tutorEvaluationSkill)) {
            // set the owning side to null (unless already changed)
            if ($tutorEvaluationSkill->getSkillLevel() === $this) {
                $tutorEvaluationSkill->setSkillLevel(null);
            }
        }

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

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

    public function getOrderIndex(): ?int
    {
        return $this->order_index;
    }

    public function setOrderIndex(int $order_index): static
    {
        $this->order_index = $order_index;

        return $this;
    }
}
