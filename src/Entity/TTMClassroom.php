<?php

namespace App\Entity;

use App\Repository\TTMClassroomRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TTMClassroomRepository::class)]
class TTMClassroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\ManyToOne(inversedBy: 'ttmClassrooms')]
    private ?User $ttm = null;

    #[ORM\ManyToOne(inversedBy: 'ttmClassrooms')]
    private ?Classroom $classroom = null;

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

    public function getTtm(): ?User
    {
        return $this->ttm;
    }

    public function setTtm(?User $ttm): static
    {
        $this->ttm = $ttm;
        return $this;
    }

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): static
    {
        $this->classroom = $classroom;
        return $this;
    }
}
