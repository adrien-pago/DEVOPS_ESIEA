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
        parent::setUp();
        
        $uniqueId = uniqid();
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => 'quiz_test_' . $uniqueId . '@example.com',
            'PHP_AUTH_PW' => 'password123'
        ]);
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->quizRepository = static::getContainer()->get(QuizRepository::class);

        // Create test user
        $this->user = new User();
        $this->user->setEmail('quiz_test_' . $uniqueId . '@example.com');
        $this->user->setUsername('quizuser_test_' . $uniqueId);
        $hashedPassword = $this->passwordHasher->hashPassword($this->user, 'password123');
        $this->user->setPassword($hashedPassword);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if ($this->entityManager) {
            // Supprimer uniquement les quiz créés par l'utilisateur de test
            if ($this->user) {
                try {
                    // Rafraîchir l'utilisateur
                    $this->entityManager->refresh($this->user);
                    
                    // Récupérer les quiz de l'utilisateur
                    $userQuizzes = $this->quizRepository->findBy(['author' => $this->user]);
                    
                    foreach ($userQuizzes as $quiz) {
                        // Rafraîchir l'entité quiz
                        $this->entityManager->refresh($quiz);
                        
                        foreach ($quiz->getQuestions() as $question) {
                            $this->entityManager->refresh($question);
                            foreach ($question->getAnswers() as $answer) {
                                $this->entityManager->refresh($answer);
                                $this->entityManager->remove($answer);
                            }
                            $this->entityManager->remove($question);
                        }
                        $this->entityManager->remove($quiz);
                        $this->entityManager->flush();
                    }
                    
                    // Supprimer l'utilisateur
                    $this->entityManager->remove($this->user);
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    // Les entités peuvent déjà avoir été supprimées
                }
            }
            
            $this->entityManager->close();
            $this->entityManager = null;
        }
        
        $this->client = null;
    }

    public function testCreateQuiz(): void
    {
        // S'assurer que l'utilisateur est persisté et attaché à l'EntityManager
        $this->entityManager->refresh($this->user);
        
        // Vérifier que l'utilisateur existe dans la base de données
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($this->user->getId());
        $this->assertNotNull($user, 'L\'utilisateur doit exister dans la base de données');
        
        $quizData = [
            'title' => 'Test Quiz',
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
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ], json_encode($quizData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('Test Quiz', $response['title']);
        $this->assertCount(2, $response['questions']);
    }

    public function testGetQuiz(): void
    {
        // Create a quiz for testing
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setAuthor($this->user);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $quizId = $quiz->getId();

        // Make sure the user exists in the database
        $this->entityManager->refresh($this->user);

        $this->client->request('GET', '/api/quiz/' . $quizId);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Quiz', $response['title']);
    }

    public function testListQuizzes(): void
    {
        // S'assurer que l'utilisateur est persisté
        $this->entityManager->refresh($this->user);
        
        // Vérifier que l'utilisateur existe dans la base de données
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($this->user->getId());
        $this->assertNotNull($user, 'L\'utilisateur doit exister dans la base de données');
        
        // Create some quizzes for testing
        for ($i = 1; $i <= 3; $i++) {
            $quiz = new Quiz();
            $quiz->setTitle('Test Quiz ' . $i);
            $quiz->setTheme('Test Theme');
            $quiz->setAuthor($user); // Utiliser l'utilisateur récupéré de la base de données
            $this->entityManager->persist($quiz);
            $this->entityManager->flush(); // Flush après chaque quiz pour s'assurer qu'il est bien persisté
        }

        $this->client->request('GET', '/api/quiz');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(3, count($response));
    }

    public function testUpdateQuiz(): void
    {
        // Create a quiz for testing
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setAuthor($this->user);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $updateData = [
            'title' => 'Updated Quiz',
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
        // Create a quiz for testing
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
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