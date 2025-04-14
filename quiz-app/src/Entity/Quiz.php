<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['quiz:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['quiz:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['quiz:read'])]
    private ?string $theme = null;

    #[ORM\Column]
    #[Groups(['quiz:read'])]
    private ?bool $moderated = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['quiz:read'])]
    private ?User $author = null;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: Question::class, orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['quiz:read'])]
    private Collection $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function isModerated(): ?bool
    {
        return $this->moderated;
    }

    public function setModerated(bool $moderated): static
    {
        $this->moderated = $moderated;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }

        return $this;
    }

    /**
     * Calcule le score d'un quiz en fonction des réponses données
     * 
     * @param array $answers Les réponses données, format: ['questionText' => 'choiceIndex']
     * @return float Le score obtenu (entre 0 et 100)
     */
    public function calculateScore(array $answers): float
    {
        if (empty($this->questions) || empty($answers)) {
            return 0.0;
        }

        $correctAnswers = 0;
        $totalQuestions = count($this->questions);

        foreach ($this->questions as $question) {
            $questionText = $question->getText();
            if (!isset($answers[$questionText])) {
                continue;
            }

            $givenAnswer = $answers[$questionText];
            if ($question->getCorrectChoice() === $givenAnswer) {
                $correctAnswers++;
            }
        }

        return ($correctAnswers / $totalQuestions) * 100;
    }
} 