<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
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

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $surname = null;

    #[ORM\Column]
    private ?int $aisId = null;

    /**
     * @var Collection|Priklad[]
     *
     * @ORM\ManyToMany(targetEntity=Priklad::class, inversedBy="users")
     * @ORM\JoinTable(
     *     name="user_priklad",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="priklad_id", referencedColumnName="id")}
     * )
     */
    private Collection|array $priklady;

    /**
        #[ORM\ManyToMany(targetEntity: Kolekcia::class, inversedBy: 'users')]
        #[ORM\JoinTable(name: 'kolekcia_user',
        joinColumns: [#[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]],
        inverseJoinColumns: [#[ORM\JoinColumn(name: 'kolekcia_id', referencedColumnName: 'id')]] )]
     */
    private Collection $kolekcias;


    public function __construct()
    {
        $this->priklady = new ArrayCollection();
        $this->kolekcias = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getPriklady(): Collection
    {
        return $this->priklady;
    }
    public function getKolekcias(): Collection
    {
        return $this->kolekcias;
    }

    public function addKolekcia(Kolekcia $kolekcia): self
    {
        if (!$this->kolekcias->contains($kolekcia)) {
            $this->kolekcias[] = $kolekcia;
        }

        return $this;
    }

    public function removeKolekcia(Kolekcia $kolekcia): self
    {
        if ($this->kolekcias->contains($kolekcia)) {
            $this->kolekcias->removeElement($kolekcia);
            $kolekcia->removeUser($this);
        }

        return $this;
    }

    public function addPriklad(Priklad $priklad): self
    {
        if (!$this->priklady->contains($priklad)) {
            $this->priklady[] = $priklad;
        }

        return $this;
    }

    public function removePriklad(Priklad $priklad): self
    {
        if ($this->priklady->removeElement($priklad)) {
            $priklad->removeUser($this);
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAisId(): ?int
    {
        return $this->aisId;
    }

    public function setAisId(?int $aisId): self
    {
        $this->aisId = $aisId;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
