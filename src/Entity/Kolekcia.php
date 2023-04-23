<?php

namespace App\Entity;

use App\Repository\KolekciaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: KolekciaRepository::class)]
class Kolekcia
{
    #[ORM\Column(nullable: true)]
    private ?int $maxPoints = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dateToOpen = null;

    #[ORM\Column(nullable: true)]
    private array $student = [];

    #[ORM\Column(nullable: true)]
    private ?int $teacher = null;


    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeKolekcia($this);
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPriklady(): Collection
    {
        return $this->priklady;
    }

    public function addPriklad(Priklad $priklad): self
    {
        if (!$this->priklady->contains($priklad)) {
            $this->priklady[] = $priklad;
            $priklad->setKolekcia($this);
        }

        return $this;
    }

    public function removePriklad(Priklad $priklad): self
    {
        if ($this->priklady->removeElement($priklad)) {
            if ($priklad->getKolekcia() === $this) {
                $priklad->setKolekcia(null);
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollectionId(): ?int
    {
        return $this->collectionId;
    }

    public function setCollectionId(int $collectionId): self
    {
        $this->collectionId = $collectionId;

        return $this;
    }

    public function getNameOfBlock(): ?string
    {
        return $this->name;
    }

    public function setNameOfBlock(string $nameOfBlock): self
    {
        $this->name = $nameOfBlock;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDateToOpen(): ?string
    {
        return $this->dateToOpen;
    }

    /**
     * @param string|null $dateToOpen
     */
    public function setDateToOpen(?string $dateToOpen): void
    {
        $this->dateToOpen = $dateToOpen;
    }

    /**
     * @return int|null
     */
    public function getMaxPoints(): ?int
    {
        return $this->maxPoints;
    }

    /**
     * @param int|null $maxPoints
     */
    public function setMaxPoints(?int $maxPoints): void
    {
        $this->maxPoints = $maxPoints;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getStudent(): array
    {
        return $this->student;
    }

    /**
     * @param array $student
     */
    public function setStudent(array $student): void
    {
        $this->student = $student;
    }

    /**
     * @return int|null
     */
    public function getTeacher(): ?int
    {
        return $this->teacher;
    }

    /**
     * @param int|null $teacher
     */
    public function setTeacher(?int $teacher): void
    {
        $this->teacher = $teacher;
    }



}
