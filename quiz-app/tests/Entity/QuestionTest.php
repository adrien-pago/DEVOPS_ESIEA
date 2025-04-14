<?php

namespace App\Tests\Entity;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\Answer;
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
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('testuser@example.com');
        $user->setPassword('password123');
        
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz Title');
        $quiz->setTheme('Géographie');
        $quiz->setAuthor($user);
        
        $this->question->setQuiz($quiz);
        $this->assertSame($quiz, $this->question->getQuiz());
    }

    public function testQuestionAnswers(): void
    {
        $answer1 = new Answer();
        $answer1->setSelectedChoice(0);
        $answer1->setIsCorrect(true);
        $this->question->addAnswer($answer1);

        $answer2 = new Answer();
        $answer2->setSelectedChoice(1);
        $answer2->setIsCorrect(false);
        $this->question->addAnswer($answer2);

        $this->assertCount(2, $this->question->getAnswers());
        $this->assertTrue($this->question->getAnswers()->first()->isCorrect());
        $this->assertFalse($this->question->getAnswers()->last()->isCorrect());
    }
} 