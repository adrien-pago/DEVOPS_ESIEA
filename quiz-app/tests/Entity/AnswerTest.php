<?php

namespace App\Tests\Entity;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use PHPUnit\Framework\TestCase;

class AnswerTest extends TestCase
{
    private Answer $answer;
    private Question $question;

    protected function setUp(): void
    {
        $this->answer = new Answer();
        $this->question = new Question();
        $this->question->setQuiz(new Quiz());
    }

    public function testAnswerCreation(): void
    {
        $this->assertInstanceOf(Answer::class, $this->answer);
        $this->assertNotNull($this->answer->getAnsweredAt());
    }

    public function testAnswerQuestionAssociation(): void
    {
        $this->answer->setQuestion($this->question);
        $this->assertSame($this->question, $this->answer->getQuestion());
    }

    public function testAnswerCorrectness(): void
    {
        $this->answer->setIsCorrect(true);
        $this->assertTrue($this->answer->isCorrect());

        $this->answer->setIsCorrect(false);
        $this->assertFalse($this->answer->isCorrect());
    }

    public function testAnswerTimestamp(): void
    {
        $timestamp = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->answer->setAnsweredAt($timestamp);
        $this->assertSame($timestamp, $this->answer->getAnsweredAt());
    }
} 