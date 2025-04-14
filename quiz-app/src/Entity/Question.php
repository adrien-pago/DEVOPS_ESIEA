<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $text = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\Column(type: 'json')]
    private array $choices = [];

    #[ORM\Column]
    private ?int $correctChoice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function setChoices(array $choices): static
    {
        $this->choices = $choices;
        return $this;
    }

    public function getCorrectChoice(): ?int
    {
        return $this->correctChoice;
    }

    public function setCorrectChoice(int $correctChoice): static
    {
        $this->correctChoice = $correctChoice;
        return $this;
    }
} 