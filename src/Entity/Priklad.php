<?php

namespace App\Entity;

use App\Repository\PrikladRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrikladRepository::class)]
class Priklad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $maxPoints = null;

    #[ORM\Column(nullable: true)]
    private ?int $gainedPoints = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $result = null;

    #[ORM\Column]
    private ?bool $isCorrect = null;

    #[ORM\Column]
    private ?bool $isSubmitted = null;

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
}
