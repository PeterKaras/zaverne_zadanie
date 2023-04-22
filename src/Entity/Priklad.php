<?php

namespace App\Entity;

use App\Repository\PrikladRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: PrikladRepository::class)]
class Priklad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $collectionId = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $assignment = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxPoints = null;

    #[ORM\Column(nullable: true)]
    private ?int $gainedPoints = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCorrect = false;

    #[ORM\Column(nullable: true)]
    private ?bool $isSubmitted = false;

    #[ORM\Column(length: 255)]
    private ?string $solution = null;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="priklady")
     */
    private Collection $users;

    /**
     * @ORM\ManyToOne(targetEntity=Kolekcia::class, inversedBy="priklady")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Kolekcia $kolekcia;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getKolekcia(): ?Kolekcia
    {
        return $this->kolekcia;
    }

    public function setKolekcia(?Kolekcia $kolekcia): self
    {
        $this->kolekcia = $kolekcia;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCollectionId(): ?string
    {
        return $this->collectionId;
    }

    /**
     * @return array
     */
    public function getAssignment(): array
    {
        return $this->assignment;
    }

    /**
     * @param array $assignment
     */
    public function setAssignment(array $assignment): void
    {
        $this->assignment = $assignment;
    }

    /**
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     */
    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string|null
     */
    public function getSolution(): ?string
    {
        return $this->solution;
    }

    /**
     * @param string|null $solution
     */
    public function setSolution(?string $solution): void
    {
        $this->solution = $solution;
    }

    /**
     * @param string|null $collectionId
     */
    public function setCollectionId(?string $collectionId): void
    {
        $this->collectionId = $collectionId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaxPoints(): ?int
    {
        return $this->maxPoints;
    }

    public function setMaxPoints(int $maxPoints): self
    {
        $this->maxPoints = $maxPoints;

        return $this;
    }

    public function getGainedPoints(): ?int
    {
        return $this->gainedPoints;
    }

    public function setGainedPoints(?int $gainedPoints): self
    {
        $this->gainedPoints = $gainedPoints;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function isIsCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;

        return $this;
    }

    public function isIsSubmitted(): ?bool
    {
        return $this->isSubmitted;
    }

    public function setIsSubmitted(bool $isSubmitted): self
    {
        $this->isSubmitted = $isSubmitted;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addPriklad($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removePriklad($this);
        }

        return $this;
    }
}
