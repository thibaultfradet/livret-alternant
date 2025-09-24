<?php

namespace App\Entity;

use App\Repository\BehaviorLevelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BehaviorLevelRepository::class)]
class BehaviorLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private ?int $levelNumber = null;

    #[ORM\ManyToOne(inversedBy: 'behaviorLevels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BehaviorCriteria $behaviorCriteria = null;


    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $disabledAt = null;
    

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

    public function getLevelNumber(): ?int
    {
        return $this->levelNumber;
    }

    public function setLevelNumber(int $levelNumber): static
    {
        $this->levelNumber = $levelNumber;

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

    public function getBehaviorCriteria(): ?BehaviorCriteria
    {
        return $this->behaviorCriteria;
    }

    public function setBehaviorCriteria(?BehaviorCriteria $behaviorCriteria): static
    {
        $this->behaviorCriteria = $behaviorCriteria;

        return $this;
    }
}
