<?php

namespace App\Tests\Functional;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Answer;
use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class QuizFunctionalTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $quizRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->quizRepository = static::getContainer()->get(QuizRepository::class);
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
        $this->client->request('POST', '/api/quiz', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Test Quiz',
            'theme' => 'Test Theme',
            'questions' => [
                [
                    'text' => 'Test Question 1',
                    'answers' => [
                        ['text' => 'Answer 1', 'isCorrect' => true],
                        ['text' => 'Answer 2', 'isCorrect' => false],
                        ['text' => 'Answer 3', 'isCorrect' => false]
                    ]
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
        
        $question = new Question();
        $question->setText('Test Question');
        $question->setQuiz($quiz);
        
        $answer = new Answer();
        $answer->setText('Test Answer');
        $answer->setIsCorrect(true);
        $answer->setQuestion($question);
        
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
        $quiz->setModerated(false);
        
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
        
        $question = new Question();
        $question->setText('Test Question');
        $question->setQuiz($quiz);
        
        $correctAnswer = new Answer();
        $correctAnswer->setText('Correct Answer');
        $correctAnswer->setIsCorrect(true);
        $correctAnswer->setQuestion($question);
        
        $wrongAnswer = new Answer();
        $wrongAnswer->setText('Wrong Answer');
        $wrongAnswer->setIsCorrect(false);
        $wrongAnswer->setQuestion($question);
        
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Soumettre les réponses
        $this->client->request('POST', '/api/quiz/' . $quiz->getId() . '/submit', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'answers' => [
                [
                    'questionId' => $question->getId(),
                    'answerId' => $correctAnswer->getId()
                ]
            ]
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('score', $response);
        $this->assertEquals(100, $response['score']); // 100% car une seule question correcte
    }
} 