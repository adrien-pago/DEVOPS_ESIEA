<?php

namespace App\Tests\Functional;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\User;
use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class QuizFunctionalTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $quizRepository;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->quizRepository = static::getContainer()->get(QuizRepository::class);

        // Créer un utilisateur de test
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setUsername('testuser');
        $this->user->setPassword('password123');
        $this->user->setRoles(['ROLE_USER']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($this->user, 'password123');
        $this->user->setPassword($hashedPassword);

        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        // Authentifier l'utilisateur
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $response['token']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->user) {
            $this->entityManager->remove($this->user);
            $this->entityManager->flush();
        }
        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
    }

    public function testCreateQuiz(): void
    {
        $this->client->request('POST', '/api/quiz', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Test Quiz',
            'theme' => 'Test Theme',
            'questions' => [
                [
                    'text' => 'Test Question 1',
                    'choices' => ['Answer 1', 'Answer 2', 'Answer 3'],
                    'correctChoice' => 0
                ]
            ]
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);

        // Vérifier que le quiz a été créé en base de données
        $quiz = $this->quizRepository->find($response['id']);
        $this->assertNotNull($quiz);
        $this->assertEquals('Test Quiz', $quiz->getTitle());
        $this->assertEquals('Test Theme', $quiz->getTheme());
        $this->assertCount(1, $quiz->getQuestions());
    }

    public function testGetQuiz(): void
    {
        // Créer un quiz de test
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setCreator($this->user);
        
        $question = new Question();
        $question->setText('Test Question');
        $question->setQuiz($quiz);
        $question->setChoices(['Choice 1', 'Choice 2', 'Choice 3']);
        $question->setCorrectChoice(0);
        
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Tester la récupération du quiz
        $this->client->request('GET', '/api/quiz/' . $quiz->getId());
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('Test Quiz', $response['title']);
        $this->assertEquals('Test Theme', $response['theme']);
        $this->assertCount(1, $response['questions']);
    }

    public function testModerateQuiz(): void
    {
        // Créer un quiz non modéré
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setCreator($this->user);
        $quiz->setModerated(false);
        
        $question = new Question();
        $question->setText('Test Question');
        $question->setQuiz($quiz);
        $question->setChoices(['Choice 1', 'Choice 2', 'Choice 3']);
        $question->setCorrectChoice(0);
        
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Modérer le quiz
        $this->client->request('PUT', '/api/quiz/' . $quiz->getId() . '/moderate', [], [], ['CONTENT_TYPE' => 'application/json']);
        $this->assertResponseIsSuccessful();

        // Vérifier que le quiz est maintenant modéré
        $this->entityManager->refresh($quiz);
        $this->assertTrue($quiz->isModerated());
    }

    public function testSubmitQuizAnswers(): void
    {
        // Créer un quiz avec une question
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setCreator($this->user);
        
        $question = new Question();
        $question->setText('Test Question');
        $question->setQuiz($quiz);
        $question->setChoices(['Choice 1', 'Choice 2', 'Choice 3']);
        $question->setCorrectChoice(0);
        
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Soumettre les réponses
        $this->client->request('POST', '/api/quiz/' . $quiz->getId() . '/submit', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'answers' => [
                [
                    'questionId' => $question->getId(),
                    'selectedChoice' => 0
                ]
            ]
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('score', $response);
        $this->assertEquals(100, $response['score']); // 100% car une seule question correcte
    }
} 