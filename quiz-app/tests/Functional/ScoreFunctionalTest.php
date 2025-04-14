<?php

namespace App\Tests\Functional;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\User;
use App\Entity\QuizResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ScoreFunctionalTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private $user;
    private $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => 'score_test@example.com',
            'PHP_AUTH_PW' => 'password123'
        ]);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Create test user
        $this->user = new User();
        $this->user->setEmail('score_test@example.com');
        $this->user->setUsername('scoreuser_test');
        $hashedPassword = $this->passwordHasher->hashPassword($this->user, 'password123');
        $this->user->setPassword($hashedPassword);
        $this->entityManager->persist($this->user);

        // Create test quiz
        $this->quiz = new Quiz();
        $this->quiz->setTitle('Test Quiz for Score');
        $this->quiz->setTheme('Test Theme');
        $this->quiz->setModerated(true);
        $this->quiz->setAuthor($this->user);

        // Add two questions to the quiz
        $question1 = new Question();
        $question1->setText('Question 1');
        $question1->setQuiz($this->quiz);

        $correctAnswer1 = new Answer();
        $correctAnswer1->setText('Correct Answer 1');
        $correctAnswer1->setIsCorrect(true);
        $correctAnswer1->setSelectedChoice(0);
        $correctAnswer1->setQuestion($question1);

        $wrongAnswer1 = new Answer();
        $wrongAnswer1->setText('Wrong Answer 1');
        $wrongAnswer1->setIsCorrect(false);
        $wrongAnswer1->setSelectedChoice(1);
        $wrongAnswer1->setQuestion($question1);

        $question2 = new Question();
        $question2->setText('Question 2');
        $question2->setQuiz($this->quiz);

        $correctAnswer2 = new Answer();
        $correctAnswer2->setText('Correct Answer 2');
        $correctAnswer2->setIsCorrect(true);
        $correctAnswer2->setSelectedChoice(0);
        $correctAnswer2->setQuestion($question2);

        $wrongAnswer2 = new Answer();
        $wrongAnswer2->setText('Wrong Answer 2');
        $wrongAnswer2->setIsCorrect(false);
        $wrongAnswer2->setSelectedChoice(1);
        $wrongAnswer2->setQuestion($question2);

        $this->entityManager->persist($this->quiz);
        $this->entityManager->persist($question1);
        $this->entityManager->persist($question2);
        $this->entityManager->persist($correctAnswer1);
        $this->entityManager->persist($wrongAnswer1);
        $this->entityManager->persist($correctAnswer2);
        $this->entityManager->persist($wrongAnswer2);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        if ($this->quiz) {
            $this->entityManager->remove($this->quiz);
        }
        if ($this->user) {
            $this->entityManager->remove($this->user);
        }
        $this->entityManager->flush();
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
    }

    public function testSubmitQuizWithPerfectScore(): void
    {
        // Submit answers (all correct)
        $questions = $this->quiz->getQuestions();
        $answers = [];
        foreach ($questions as $question) {
            foreach ($question->getAnswers() as $answer) {
                if ($answer->isCorrect()) {
                    $answers[] = [
                        'questionId' => $question->getId(),
                        'answerId' => $answer->getId()
                    ];
                    break;
                }
            }
        }

        $this->client->request('POST', '/api/quiz/' . $this->quiz->getId() . '/submit', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ], json_encode(['answers' => $answers]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('score', $response);
        $this->assertEquals(100, $response['score']);

        // Vérifier que le résultat a été enregistré
        $result = $this->entityManager->getRepository(QuizResult::class)->findOneBy([
            'user' => $this->user,
            'quiz' => $this->quiz
        ]);
        $this->assertNotNull($result);
        $this->assertEquals(100, $result->getScore());
    }

    public function testSubmitQuizWithPartialScore(): void
    {
        // Submit answers (one correct, one incorrect)
        $questions = $this->quiz->getQuestions();
        $answers = [];
        
        // First question correct
        foreach ($questions[0]->getAnswers() as $answer) {
            if ($answer->isCorrect()) {
                $answers[] = [
                    'questionId' => $questions[0]->getId(),
                    'answerId' => $answer->getId()
                ];
                break;
            }
        }

        // Second question incorrect
        foreach ($questions[1]->getAnswers() as $answer) {
            if (!$answer->isCorrect()) {
                $answers[] = [
                    'questionId' => $questions[1]->getId(),
                    'answerId' => $answer->getId()
                ];
                break;
            }
        }

        $this->client->request('POST', '/api/quiz/' . $this->quiz->getId() . '/submit', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ], json_encode(['answers' => $answers]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('score', $response);
        $this->assertEquals(50, $response['score']);

        // Vérifier que le résultat a été enregistré
        $result = $this->entityManager->getRepository(QuizResult::class)->findOneBy([
            'user' => $this->user,
            'quiz' => $this->quiz
        ]);
        $this->assertNotNull($result);
        $this->assertEquals(50, $result->getScore());
    }

    public function testGetUserQuizHistory(): void
    {
        // Créer quelques résultats de quiz
        $result1 = new QuizResult();
        $result1->setUser($this->user);
        $result1->setQuiz($this->quiz);
        $result1->setScore(100);
        $result1->setCompletedAt(new \DateTime());

        $result2 = new QuizResult();
        $result2->setUser($this->user);
        $result2->setQuiz($this->quiz);
        $result2->setScore(50);
        $result2->setCompletedAt(new \DateTime());

        $this->entityManager->persist($result1);
        $this->entityManager->persist($result2);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/user/quiz-history');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(2, $response);
    }
} 