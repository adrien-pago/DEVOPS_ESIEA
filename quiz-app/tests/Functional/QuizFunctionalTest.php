<?php

namespace App\Tests\Functional;

use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class QuizFunctionalTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private $quizRepository;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->quizRepository = static::getContainer()->get(QuizRepository::class);

        // Créer un utilisateur pour les tests
        $this->user = new User();
        $this->user->setEmail('quiz@example.com');
        $this->user->setUsername('quizuser');
        $hashedPassword = $this->passwordHasher->hashPassword($this->user, 'password123');
        $this->user->setPassword($hashedPassword);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        // Se connecter
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'quiz@example.com',
            'password' => 'password123'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
    }

    public function testCreateQuiz(): void
    {
        $quizData = [
            'title' => 'Test Quiz',
            'description' => 'A test quiz',
            'theme' => 'Test Theme',
            'questions' => [
                [
                    'text' => 'Question 1',
                    'answers' => [
                        ['text' => 'Answer 1', 'isCorrect' => true],
                        ['text' => 'Answer 2', 'isCorrect' => false]
                    ]
                ],
                [
                    'text' => 'Question 2',
                    'answers' => [
                        ['text' => 'Answer 3', 'isCorrect' => false],
                        ['text' => 'Answer 4', 'isCorrect' => true]
                    ]
                ]
            ]
        ];

        $this->client->request('POST', '/api/quiz', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($quizData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('Test Quiz', $response['title']);
        $this->assertCount(2, $response['questions']);
    }

    public function testGetQuiz(): void
    {
        // Créer un quiz pour le test
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('A test quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setAuthor($this->user);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/quiz/' . $quiz->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Quiz', $response['title']);
    }

    public function testListQuizzes(): void
    {
        // Créer quelques quiz pour le test
        for ($i = 1; $i <= 3; $i++) {
            $quiz = new Quiz();
            $quiz->setTitle('Test Quiz ' . $i);
            $quiz->setDescription('A test quiz');
            $quiz->setTheme('Test Theme');
            $quiz->setAuthor($this->user);
            $this->entityManager->persist($quiz);
        }
        $this->entityManager->flush();

        $this->client->request('GET', '/api/quiz');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(3, count($response));
    }

    public function testUpdateQuiz(): void
    {
        // Créer un quiz pour le test
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('A test quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setAuthor($this->user);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $updateData = [
            'title' => 'Updated Quiz',
            'description' => 'An updated quiz',
            'theme' => 'Updated Theme'
        ];

        $this->client->request('PUT', '/api/quiz/' . $quiz->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($updateData));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Quiz', $response['title']);
    }

    public function testDeleteQuiz(): void
    {
        // Créer un quiz pour le test
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('A test quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setAuthor($this->user);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $quizId = $quiz->getId();

        $this->client->request('DELETE', '/api/quiz/' . $quizId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertNull($this->quizRepository->find($quizId));
    }
} 