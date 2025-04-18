<?php

namespace App\Tests\Entity;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class QuizTest extends TestCase
{
    private Quiz $quiz;
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('testuser');
        $this->user->setEmail('testuser@example.com');
        $this->user->setPassword('password123');

        $this->quiz = new Quiz();
        $this->quiz->setTitle('Test Quiz Title');
        $this->quiz->setTheme('Test Theme');
        $this->quiz->setAuthor($this->user);
    }

    public function testQuizCreation(): void
    {
        $this->assertInstanceOf(Quiz::class, $this->quiz);
        $this->assertEquals('Test Quiz Title', $this->quiz->getTitle());
        $this->assertEquals('Test Theme', $this->quiz->getTheme());
        $this->assertFalse($this->quiz->isModerated());
        $this->assertEquals($this->user, $this->quiz->getAuthor());
    }

    public function testQuizModeration(): void
    {
        $this->quiz->setModerated(true);
        $this->assertTrue($this->quiz->isModerated());
    }

    public function testQuizAuthor(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('testuser@example.com');
        $user->setPassword('password123');
        
        $this->quiz->setAuthor($user);
        $this->assertSame($user, $this->quiz->getAuthor());
    }

    public function testCalculateScoreWithNoQuestions(): void
    {
        $score = $this->quiz->calculateScore([]);
        $this->assertEquals(0.0, $score);
    }

    public function testCalculateScoreWithAllCorrectAnswers(): void
    {
        // Créer trois questions avec leurs réponses correctes
        $question1 = new Question();
        $question1->setText('Question 1');
        $question1->setChoices(['A', 'B', 'C']);
        $question1->setCorrectChoice(0);
        $this->quiz->addQuestion($question1);

        $question2 = new Question();
        $question2->setText('Question 2');
        $question2->setChoices(['A', 'B', 'C']);
        $question2->setCorrectChoice(1);
        $this->quiz->addQuestion($question2);

        $question3 = new Question();
        $question3->setText('Question 3');
        $question3->setChoices(['A', 'B', 'C']);
        $question3->setCorrectChoice(2);
        $this->quiz->addQuestion($question3);

        // Simuler des réponses toutes correctes
        $answers = [
            'Question 1' => 0,
            'Question 2' => 1,
            'Question 3' => 2
        ];

        $score = $this->quiz->calculateScore($answers);
        $this->assertEquals(100.0, $score);
    }

    public function testCalculateScoreWithSomeCorrectAnswers(): void
    {
        // Créer trois questions
        $question1 = new Question();
        $question1->setText('Question 1');
        $question1->setChoices(['A', 'B', 'C']);
        $question1->setCorrectChoice(0);
        $this->quiz->addQuestion($question1);

        $question2 = new Question();
        $question2->setText('Question 2');
        $question2->setChoices(['A', 'B', 'C']);
        $question2->setCorrectChoice(1);
        $this->quiz->addQuestion($question2);

        $question3 = new Question();
        $question3->setText('Question 3');
        $question3->setChoices(['A', 'B', 'C']);
        $question3->setCorrectChoice(2);
        $this->quiz->addQuestion($question3);

        // Simuler des réponses avec seulement une bonne réponse
        $answers = [
            'Question 1' => 0, // Correct
            'Question 2' => 2, // Incorrect
            'Question 3' => 1  // Incorrect
        ];

        $score = $this->quiz->calculateScore($answers);
        $this->assertEqualsWithDelta(33.33, $score, 0.01);
    }

    public function testCalculateScoreWithMissingAnswers(): void
    {
        // Créer trois questions
        $question1 = new Question();
        $question1->setText('Question 1');
        $question1->setChoices(['A', 'B', 'C']);
        $question1->setCorrectChoice(0);
        $this->quiz->addQuestion($question1);

        $question2 = new Question();
        $question2->setText('Question 2');
        $question2->setChoices(['A', 'B', 'C']);
        $question2->setCorrectChoice(1);
        $this->quiz->addQuestion($question2);

        $question3 = new Question();
        $question3->setText('Question 3');
        $question3->setChoices(['A', 'B', 'C']);
        $question3->setCorrectChoice(2);
        $this->quiz->addQuestion($question3);

        // Simuler des réponses avec une réponse manquante
        $answers = [
            'Question 1' => 0, // Correct
            'Question 2' => 1  // Correct
            // Question 3 manquante
        ];

        $score = $this->quiz->calculateScore($answers);
        $this->assertEqualsWithDelta(66.67, $score, 0.01);
    }
} 