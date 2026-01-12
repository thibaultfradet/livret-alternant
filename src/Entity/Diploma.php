<?php

namespace App\Entity;

use App\Repository\DiplomaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiplomaRepository::class)]
class Diploma
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, Classroom>
     */
    #[ORM\OneToMany(targetEntity: Classroom::class, mappedBy: 'diploma')]
    private Collection $classrooms;

    /**
     * @var Collection<int, SkillGroup>
     */
    #[ORM\OneToMany(targetEntity: SkillGroup::class, mappedBy: 'diploma')]
    private Collection $skillGroups;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $disabledAt = null;

    public function __construct()
    {
        $this->classrooms = new ArrayCollection();
        $this->skillGroups = new ArrayCollection();
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
            $classroom->setDiploma($this);
        }

        return $this;
    }

    public function removeClassroom(Classroom $classroom): static
    {
        if ($this->classrooms->removeElement($classroom)) {
            // set the owning side to null (unless already changed)
            if ($classroom->getDiploma() === $this) {
                $classroom->setDiploma(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SkillGroup>
     */
    public function getSkillGroups(): Collection
    {
        return $this->skillGroups;
    }

    public function addSkillGroup(SkillGroup $skillGroup): static
    {
        if (!$this->skillGroups->contains($skillGroup)) {
            $this->skillGroups->add($skillGroup);
            $skillGroup->setDiploma($this);
        }

        return $this;
    }

    public function removeSkillGroup(SkillGroup $skillGroup): static
    {
        if ($this->skillGroups->removeElement($skillGroup)) {
            // set the owning side to null (unless already changed)
            if ($skillGroup->getDiploma() === $this) {
                $skillGroup->setDiploma(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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
}
