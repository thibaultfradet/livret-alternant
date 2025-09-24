<?php

namespace App\Entity;

use App\Repository\BehaviorCriteriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BehaviorCriteriaRepository::class)]
class BehaviorCriteria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\OneToMany(mappedBy: 'behaviorCriteria', targetEntity: BehaviorLevel::class)]
    private Collection $behaviorLevels;


    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $disabledAt = null;

    public function __construct()
    {
        $this->behaviorLevels = new ArrayCollection();
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
     * @return Collection<int, BehaviorLevel>
     */
    public function getBehaviorLevels(): Collection
    {
        return $this->behaviorLevels;
    }

    public function addBehaviorLevel(BehaviorLevel $behaviorLevel): static
    {
        if (!$this->behaviorLevels->contains($behaviorLevel)) {
            $this->behaviorLevels->add($behaviorLevel);
            $behaviorLevel->setBehaviorCriteria($this);
        }

        return $this;
    }

    public function removeBehaviorLevel(BehaviorLevel $behaviorLevel): static
    {
        if ($this->behaviorLevels->removeElement($behaviorLevel)) {
            // set the owning side to null (unless already changed)
            if ($behaviorLevel->getBehaviorCriteria() === $this) {
                $behaviorLevel->setBehaviorCriteria(null);
            }
        }

        return $this;
    }
}
