<?php

namespace App\Entity;

use App\Repository\DiplomaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @var Collection<int, SkillCriteria>
     */
    #[ORM\OneToMany(targetEntity: SkillCriteria::class, mappedBy: 'diploma')]
    private Collection $skillCriteria;

    public function __construct()
    {
        $this->classrooms = new ArrayCollection();
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
            $skillCriterion->setDiploma($this);
        }

        return $this;
    }

    public function removeSkillCriterion(SkillCriteria $skillCriterion): static
    {
        if ($this->skillCriteria->removeElement($skillCriterion)) {
            // set the owning side to null (unless already changed)
            if ($skillCriterion->getDiploma() === $this) {
                $skillCriterion->setDiploma(null);
            }
        }

        return $this;
    }
}
