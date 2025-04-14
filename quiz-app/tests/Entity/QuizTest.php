<?php

namespace App\Tests\Entity;

use App\Entity\Quiz;
use App\Entity\Question;
use PHPUnit\Framework\TestCase;

class QuizTest extends TestCase
{
    private Quiz $quiz;

    protected function setUp(): void
    {
        $this->quiz = new Quiz();
    }

    public function testQuizDefaultValues(): void
    {
        $this->assertNull($this->quiz->getId());
        $this->assertNull($this->quiz->getTheme());
        $this->assertFalse($this->quiz->isModerated());
        $this->assertCount(0, $this->quiz->getQuestions());
    }

    public function testQuizTheme(): void
    {
        $theme = 'Mathématiques';
        $this->quiz->setTheme($theme);
        $this->assertEquals($theme, $this->quiz->getTheme());
    }

    public function testQuizModeration(): void
    {
        $this->quiz->setIsModerated(true);
        $this->assertTrue($this->quiz->isModerated());
    }

    public function testAddQuestion(): void
    {
        $question = new Question();
        $question->setText('Quelle est la capitale de la France?');
        $question->setChoices(['Paris', 'Londres', 'Berlin']);
        $question->setCorrectChoice(0);

        $this->quiz->addQuestion($question);
        $this->assertCount(1, $this->quiz->getQuestions());
        $this->assertSame($this->quiz, $question->getQuiz());
    }

    public function testRemoveQuestion(): void
    {
        $question = new Question();
        $question->setText('Quelle est la capitale de la France?');
        $question->setChoices(['Paris', 'Londres', 'Berlin']);
        $question->setCorrectChoice(0);

        $this->quiz->addQuestion($question);
        $this->assertCount(1, $this->quiz->getQuestions());

        $this->quiz->removeQuestion($question);
        $this->assertCount(0, $this->quiz->getQuestions());
        $this->assertNull($question->getQuiz());
    }
} 