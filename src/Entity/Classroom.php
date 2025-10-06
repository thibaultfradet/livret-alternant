<?php

namespace App\Entity;

use App\Repository\ClassroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassroomRepository::class)]
class Classroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'classroom')]
    private Collection $students;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?Diploma $diploma = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'principal_teacher_id', referencedColumnName: 'id', nullable: true)]
    private ?User $principalTeacher = null;


    /**
     * @var Collection<int, TTMClassroom>
     */
    #[ORM\OneToMany(targetEntity: TTMClassroom::class, mappedBy: 'classroom')]
    private Collection $ttmClassrooms;

    public function __construct()
    {
        $this->students = new ArrayCollection();
        $this->ttmClassrooms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return Collection<int, User> */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(User $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setClassroom($this);
        }
        return $this;
    }

    public function removeStudent(User $student): static
    {
        if ($this->students->removeElement($student)) {
            if ($student->getClassroom() === $this) {
                $student->setClassroom(null);
            }
        }
        return $this;
    }

    public function getSchoolYear(): ?SchoolYear
    {
        return $this->schoolYear;
    }

    public function setSchoolYear(?SchoolYear $schoolYear): static
    {
        $this->schoolYear = $schoolYear;
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

    /** @return Collection<int, TTMClassroom> */
    public function getTtmClassrooms(): Collection
    {
        return $this->ttmClassrooms;
    }

    public function addTtmClassroom(TTMClassroom $ttmClassroom): static
    {
        if (!$this->ttmClassrooms->contains($ttmClassroom)) {
            $this->ttmClassrooms->add($ttmClassroom);
            $ttmClassroom->setClassroom($this);
        }
        return $this;
    }

    public function removeTtmClassroom(TTMClassroom $ttmClassroom): static
    {
        if ($this->ttmClassrooms->removeElement($ttmClassroom)) {
            if ($ttmClassroom->getClassroom() === $this) {
                $ttmClassroom->setClassroom(null);
            }
        }
        return $this;
    }

    public function getPrincipalTeacher(): ?User
    {
        return $this->principalTeacher;
    }

    public function setPrincipalTeacher(?User $principalTeacher): static
    {
        $this->principalTeacher = $principalTeacher;

        return $this;
    }
}
