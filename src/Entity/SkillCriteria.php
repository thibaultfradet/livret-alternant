<?php

namespace App\Entity;

use App\Repository\SkillCriteriaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillCriteriaRepository::class)]
class SkillCriteria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $disabledAt = null;

    #[ORM\ManyToOne(inversedBy: 'skillCriteria')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Diploma $diploma = null;

    #[ORM\ManyToOne(inversedBy: 'skillCriteria')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SkillGroup $skillGroup = null;


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

    public function getDiploma(): ?Diploma
    {
        return $this->diploma;
    }

    public function setDiploma(?Diploma $diploma): static
    {
        $this->diploma = $diploma;
        return $this;
    }

    public function getSkillGroup(): ?SkillGroup
    {
        return $this->skillGroup;
    }

    public function setSkillGroup(?SkillGroup $skillGroup): static
    {
        $this->skillGroup = $skillGroup;

        return $this;
    }
}
