<?php

namespace App\Entity;

use App\Repository\ClassroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::TEXT,nullable: true)]
    private ?string $termsConditionsPro = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $termsConditionsApprentissage = null;

    #[ORM\Column(nullable: true)]
    private ?array $formationCenter = null;



    public function __construct()
    {
        $this->students = new ArrayCollection();
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

    public function getPrincipalTeacher(): ?User
    {
        return $this->principalTeacher;
    }

    public function setPrincipalTeacher(?User $principalTeacher): static
    {
        $this->principalTeacher = $principalTeacher;

        return $this;
    }

    public function getTermsConditionsPro(): ?string
    {
        return $this->termsConditionsPro;
    }

    public function setTermsConditionsPro(string $termsConditionsPro): static
    {
        $this->termsConditionsPro = $termsConditionsPro;

        return $this;
    }

    public function getTermsConditionsApprentissage(): ?string
    {
        return $this->termsConditionsApprentissage;
    }

    public function setTermsConditionsApprentissage(?string $termsConditionsApprentissage): static
    {
        $this->termsConditionsApprentissage = $termsConditionsApprentissage;

        return $this;
    }

    public function getFormationCenter(): ?array
    {
        return $this->formationCenter;
    }

    public function setFormationCenter(?array $formationCenter): static
    {
        $this->formationCenter = $formationCenter;

        return $this;
    }

}
