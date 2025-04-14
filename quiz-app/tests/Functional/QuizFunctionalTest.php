<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Quiz;
use App\Entity\Question;
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
    private $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->quizRepository = static::getContainer()->get(QuizRepository::class);

        // Créer un utilisateur pour les tests
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Se connecter pour obtenir un token
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
        $this->token = $response['token'];
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
        $this->client->request('POST', '/api/quiz', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ], json_encode([
            'title' => 'Test Quiz',
            'description' => 'A test quiz',
            'theme' => 'Test Theme',
            'questions' => [
                [
                    'text' => 'Question 1',
                    'answers' => [
                        ['text' => 'Answer 1', 'isCorrect' => true],
                        ['text' => 'Answer 2', 'isCorrect' => false],
                        ['text' => 'Answer 3', 'isCorrect' => false]
                    ]
                ],
                [
                    'text' => 'Question 2',
                    'answers' => [
                        ['text' => 'Answer 1', 'isCorrect' => false],
                        ['text' => 'Answer 2', 'isCorrect' => true],
                        ['text' => 'Answer 3', 'isCorrect' => false]
                    ]
                ]
            ]
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertEquals('Test Quiz', $response['title']);
        $this->assertArrayHasKey('questions', $response);
        $this->assertCount(2, $response['questions']);
    }

    public function testGetQuiz(): void
    {
        // Créer un quiz pour le test
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('A test quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setModerated(true);

        $question1 = new Question();
        $question1->setText('Question 1');
        $question1->setChoices(['Answer 1', 'Answer 2', 'Answer 3']);
        $question1->setCorrectChoice(0);
        $quiz->addQuestion($question1);

        $question2 = new Question();
        $question2->setText('Question 2');
        $question2->setChoices(['Answer 1', 'Answer 2', 'Answer 3']);
        $question2->setCorrectChoice(1);
        $quiz->addQuestion($question2);

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/quiz/' . $quiz->getId(), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Quiz', $response['title']);
        $this->assertArrayHasKey('questions', $response);
        $this->assertCount(2, $response['questions']);
    }

    public function testSubmitQuizAnswer(): void
    {
        // Créer un quiz pour le test
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('A test quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setModerated(true);

        $question1 = new Question();
        $question1->setText('Question 1');
        $question1->setChoices(['Answer 1', 'Answer 2', 'Answer 3']);
        $question1->setCorrectChoice(0);
        $quiz->addQuestion($question1);

        $question2 = new Question();
        $question2->setText('Question 2');
        $question2->setChoices(['Answer 1', 'Answer 2', 'Answer 3']);
        $question2->setCorrectChoice(1);
        $quiz->addQuestion($question2);

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/quiz/' . $quiz->getId() . '/submit', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ], json_encode([
            'answers' => [0, 1] // Réponses correctes
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('score', $response);
        $this->assertEquals(100, $response['score']); // 100% car toutes les réponses sont correctes
    }
} 