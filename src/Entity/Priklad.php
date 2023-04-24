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

    #[ORM\Column]
    private ?string $prikladId = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $data = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $maxPoints = null;

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

    #[ORM\Column(nullable: true)]
    private ?array $student;

    #[ORM\Column(nullable: true)]
    private ?int $teacher = null;

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
     * @return string|null
     */
    public function getCollectionId(): ?string
    {
        return $this->collectionId;
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
     * @return string|null
     */
    public function getPrikladId(): ?string
    {
        return $this->prikladId;
    }

    /**
     * @param string|null $prikladId
     */
    public function setPrikladId(?string $prikladId): void
    {
        $this->prikladId = $prikladId;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getStudent(): array
    {
        if ($this->student === null) {
            $this->student = [];
        }
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
