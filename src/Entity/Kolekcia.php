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

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: "kolekcias")]
    #[ORM\JoinTable(name: "user_kolekcia")]
    private Collection $users;

    #[ORM\Column(nullable: true)]
    private ?int $maxPoints = null;

    /**
     * @var Collection|Priklad[]
     *
     * @ORM\OneToMany(targetEntity=Priklad::class, mappedBy="kolekcia")
     */
    private Collection|array $priklady;


    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->priklady = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nameOfBlock = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dateToOpen = null;


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
        return $this->nameOfBlock;
    }

    public function setNameOfBlock(string $nameOfBlock): self
    {
        $this->nameOfBlock = $nameOfBlock;

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

    public function setUsers(mixed $students)
    {
    }

}
