<?php

namespace App\Entity;

use App\Repository\SkillGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillGroupRepository::class)]
class SkillGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $label = null;

    #[ORM\OneToMany(mappedBy: 'skillGroup', targetEntity: SkillCriteria::class)]
    private Collection $skillCriteria;


    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $disabledAt = null;

    public function __construct()
    {
        $this->skillCriteria = new ArrayCollection();
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
     * @return Collection<int, SkillCriteria>
     */
    public function getSkillCriteria(): Collection
    {
        return $this->skillCriteria;
    }

    public function addSkillCriterion(SkillCriteria $skillCriterion): static
    {
        if (!$this->skillCriteria->contains($skillCriterion)) {
            $this->skillCriteria->add($skillCriterion);
            $skillCriterion->setSkillGroup($this);
        }

        return $this;
    }

    public function removeSkillCriterion(SkillCriteria $skillCriterion): static
    {
        if ($this->skillCriteria->removeElement($skillCriterion)) {
            // set the owning side to null (unless already changed)
            if ($skillCriterion->getSkillGroup() === $this) {
                $skillCriterion->setSkillGroup(null);
            }
        }

        return $this;
    }
}
