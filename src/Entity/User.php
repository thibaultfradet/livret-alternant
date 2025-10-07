<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column( nullable: true)]
    private array $company = [];

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $disabledAt = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Classroom $classroom = null;

    #[ORM\OneToMany(targetEntity: TutorStudent::class, mappedBy: 'tutor')]
    private Collection $tutorContracts;

    #[ORM\OneToMany(targetEntity: TutorStudent::class, mappedBy: 'student')]
    private Collection $studentContracts;

    #[ORM\OneToMany(targetEntity: TutorEvaluation::class, mappedBy: 'tutor')]
    private Collection $tutorEvaluationsGiven;

    #[ORM\OneToMany(targetEntity: TutorEvaluation::class, mappedBy: 'student')]
    private Collection $tutorEvaluationsReceived;

    #[ORM\OneToMany(targetEntity: TTMEvaluation::class, mappedBy: 'ttm')]
    private Collection $ttmEvaluationsGiven;

    #[ORM\OneToMany(targetEntity: TTMEvaluation::class, mappedBy: 'student')]
    private Collection $ttmEvaluationsReceived;

    #[ORM\OneToMany(targetEntity: StudentEvaluation::class, mappedBy: 'student')]
    private Collection $studentEvaluations;

    #[ORM\OneToMany(targetEntity: TermsAcceptance::class, mappedBy: 'user')]
    private Collection $termsAcceptances;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    public function __construct()
    {
        $this->tutorContracts = new ArrayCollection();
        $this->studentContracts = new ArrayCollection();
        $this->tutorEvaluationsGiven = new ArrayCollection();
        $this->tutorEvaluationsReceived = new ArrayCollection();
        $this->ttmEvaluationsGiven = new ArrayCollection();
        $this->ttmEvaluationsReceived = new ArrayCollection();
        $this->studentEvaluations = new ArrayCollection();
        $this->termsAcceptances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    

    public function getDisabledAt(): ?\DateTimeInterface
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?\DateTimeInterface $disabledAt): static
    {
        $this->disabledAt = $disabledAt;
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

    /** @return Collection<int, TutorStudent> */
    public function getTutorContracts(): Collection
    {
        return $this->tutorContracts;
    }

    /** @return Collection<int, TutorStudent> */
    public function getStudentContracts(): Collection
    {
        return $this->studentContracts;
    }

    /** @return Collection<int, TutorEvaluation> */
    public function getTutorEvaluationsGiven(): Collection
    {
        return $this->tutorEvaluationsGiven;
    }

    /** @return Collection<int, TutorEvaluation> */
    public function getTutorEvaluationsReceived(): Collection
    {
        return $this->tutorEvaluationsReceived;
    }

    /** @return Collection<int, TTMEvaluation> */
    public function getTtmEvaluationsGiven(): Collection
    {
        return $this->ttmEvaluationsGiven;
    }

    /** @return Collection<int, TTMEvaluation> */
    public function getTtmEvaluationsReceived(): Collection
    {
        return $this->ttmEvaluationsReceived;
    }

    /** @return Collection<int, StudentEvaluation> */
    public function getStudentEvaluations(): Collection
    {
        return $this->studentEvaluations;
    }

    /** @return Collection<int, TermsAcceptance> */
    public function getTermsAcceptances(): Collection
    {
        return $this->termsAcceptances;
    }

    public function addTutorContract(TutorStudent $tutorContract): static
    {
        if (!$this->tutorContracts->contains($tutorContract)) {
            $this->tutorContracts->add($tutorContract);
            $tutorContract->setTutor($this);
        }

        return $this;
    }

    public function removeTutorContract(TutorStudent $tutorContract): static
    {
        if ($this->tutorContracts->removeElement($tutorContract)) {
            // set the owning side to null (unless already changed)
            if ($tutorContract->getTutor() === $this) {
                $tutorContract->setTutor(null);
            }
        }

        return $this;
    }

    public function addStudentContract(TutorStudent $studentContract): static
    {
        if (!$this->studentContracts->contains($studentContract)) {
            $this->studentContracts->add($studentContract);
            $studentContract->setStudent($this);
        }

        return $this;
    }

    public function removeStudentContract(TutorStudent $studentContract): static
    {
        if ($this->studentContracts->removeElement($studentContract)) {
            // set the owning side to null (unless already changed)
            if ($studentContract->getStudent() === $this) {
                $studentContract->setStudent(null);
            }
        }

        return $this;
    }

    public function addTutorEvaluationsGiven(TutorEvaluation $tutorEvaluationsGiven): static
    {
        if (!$this->tutorEvaluationsGiven->contains($tutorEvaluationsGiven)) {
            $this->tutorEvaluationsGiven->add($tutorEvaluationsGiven);
            $tutorEvaluationsGiven->setTutor($this);
        }

        return $this;
    }

    public function removeTutorEvaluationsGiven(TutorEvaluation $tutorEvaluationsGiven): static
    {
        if ($this->tutorEvaluationsGiven->removeElement($tutorEvaluationsGiven)) {
            // set the owning side to null (unless already changed)
            if ($tutorEvaluationsGiven->getTutor() === $this) {
                $tutorEvaluationsGiven->setTutor(null);
            }
        }

        return $this;
    }

    public function addTutorEvaluationsReceived(TutorEvaluation $tutorEvaluationsReceived): static
    {
        if (!$this->tutorEvaluationsReceived->contains($tutorEvaluationsReceived)) {
            $this->tutorEvaluationsReceived->add($tutorEvaluationsReceived);
            $tutorEvaluationsReceived->setStudent($this);
        }

        return $this;
    }

    public function removeTutorEvaluationsReceived(TutorEvaluation $tutorEvaluationsReceived): static
    {
        if ($this->tutorEvaluationsReceived->removeElement($tutorEvaluationsReceived)) {
            // set the owning side to null (unless already changed)
            if ($tutorEvaluationsReceived->getStudent() === $this) {
                $tutorEvaluationsReceived->setStudent(null);
            }
        }

        return $this;
    }

    public function addTtmEvaluationsGiven(TTMEvaluation $ttmEvaluationsGiven): static
    {
        if (!$this->ttmEvaluationsGiven->contains($ttmEvaluationsGiven)) {
            $this->ttmEvaluationsGiven->add($ttmEvaluationsGiven);
            $ttmEvaluationsGiven->setTtm($this);
        }

        return $this;
    }

    public function removeTtmEvaluationsGiven(TTMEvaluation $ttmEvaluationsGiven): static
    {
        if ($this->ttmEvaluationsGiven->removeElement($ttmEvaluationsGiven)) {
            // set the owning side to null (unless already changed)
            if ($ttmEvaluationsGiven->getTtm() === $this) {
                $ttmEvaluationsGiven->setTtm(null);
            }
        }

        return $this;
    }

    public function addTtmEvaluationsReceived(TTMEvaluation $ttmEvaluationsReceived): static
    {
        if (!$this->ttmEvaluationsReceived->contains($ttmEvaluationsReceived)) {
            $this->ttmEvaluationsReceived->add($ttmEvaluationsReceived);
            $ttmEvaluationsReceived->setStudent($this);
        }

        return $this;
    }

    public function removeTtmEvaluationsReceived(TTMEvaluation $ttmEvaluationsReceived): static
    {
        if ($this->ttmEvaluationsReceived->removeElement($ttmEvaluationsReceived)) {
            // set the owning side to null (unless already changed)
            if ($ttmEvaluationsReceived->getStudent() === $this) {
                $ttmEvaluationsReceived->setStudent(null);
            }
        }

        return $this;
    }

    public function addStudentEvaluation(StudentEvaluation $studentEvaluation): static
    {
        if (!$this->studentEvaluations->contains($studentEvaluation)) {
            $this->studentEvaluations->add($studentEvaluation);
            $studentEvaluation->setStudent($this);
        }

        return $this;
    }

    public function removeStudentEvaluation(StudentEvaluation $studentEvaluation): static
    {
        if ($this->studentEvaluations->removeElement($studentEvaluation)) {
            // set the owning side to null (unless already changed)
            if ($studentEvaluation->getStudent() === $this) {
                $studentEvaluation->setStudent(null);
            }
        }

        return $this;
    }

    public function addTermsAcceptance(TermsAcceptance $termsAcceptance): static
    {
        if (!$this->termsAcceptances->contains($termsAcceptance)) {
            $this->termsAcceptances->add($termsAcceptance);
            $termsAcceptance->setUser($this);
        }

        return $this;
    }

    public function removeTermsAcceptance(TermsAcceptance $termsAcceptance): static
    {
        if ($this->termsAcceptances->removeElement($termsAcceptance)) {
            // set the owning side to null (unless already changed)
            if ($termsAcceptance->getUser() === $this) {
                $termsAcceptance->setUser(null);
            }
        }

        return $this;
    }

  

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCompany(): ?array
    {
        return $this->company;
    }

    public function setCompany(?array $company): static
    {
        $this->company = $company;

        return $this;
    }


     public function getCompanyName(): ?string
    {
        return $this->company[0] ?? null;
    }

    public function setCompanyName(?string $name): self
    {
        $this->company[0] = $name;
        return $this;
    }

    public function getCompanyAddress(): ?string
    {
        return $this->company[1] ?? null;
    }

    public function setCompanyAddress(?string $address): self
    {
        $this->company[1] = $address;
        return $this;
    }

}
