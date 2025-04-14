<?php

namespace App\Tests\Entity;

use App\Entity\Question;
use App\Entity\Quiz;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    private Question $question;

    protected function setUp(): void
    {
        $this->question = new Question();
    }

    public function testQuestionDefaultValues(): void
    {
        $this->assertNull($this->question->getId());
        $this->assertNull($this->question->getText());
        $this->assertNull($this->question->getQuiz());
        $this->assertEmpty($this->question->getChoices());
        $this->assertNull($this->question->getCorrectChoice());
    }

    public function testQuestionText(): void
    {
        $text = 'Quelle est la capitale de la France?';
        $this->question->setText($text);
        $this->assertEquals($text, $this->question->getText());
    }

    public function testQuestionChoices(): void
    {
        $choices = ['Paris', 'Londres', 'Berlin'];
        $this->question->setChoices($choices);
        $this->assertEquals($choices, $this->question->getChoices());
    }

    public function testQuestionCorrectChoice(): void
    {
        $correctChoice = 0;
        $this->question->setCorrectChoice($correctChoice);
        $this->assertEquals($correctChoice, $this->question->getCorrectChoice());
    }

    public function testQuestionQuiz(): void
    {
        $quiz = new Quiz();
        $quiz->setTheme('Géographie');
        
        $this->question->setQuiz($quiz);
        $this->assertSame($quiz, $this->question->getQuiz());
    }
} 