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
        
        $uniqueId = uniqid();
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => 'score_test_' . $uniqueId . '@example.com',
            'PHP_AUTH_PW' => 'password123'
        ]);
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Create test user
        $this->user = new User();
        $this->user->setEmail('score_test_' . $uniqueId . '@example.com');
        $this->user->setUsername('scoreuser_test_' . $uniqueId);
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
        $question1->setChoices(['Answer 1', 'Answer 2']);
        $question1->setCorrectChoice(0);
        $this->quiz->addQuestion($question1);

        $correctAnswer1 = new Answer();
        $correctAnswer1->setText('Correct Answer 1');
        $correctAnswer1->setIsCorrect(true);
        $correctAnswer1->setSelectedChoice(0);
        $correctAnswer1->setQuestion($question1);
        $question1->addAnswer($correctAnswer1);

        $wrongAnswer1 = new Answer();
        $wrongAnswer1->setText('Wrong Answer 1');
        $wrongAnswer1->setIsCorrect(false);
        $wrongAnswer1->setSelectedChoice(1);
        $wrongAnswer1->setQuestion($question1);
        $question1->addAnswer($wrongAnswer1);

        $question2 = new Question();
        $question2->setText('Question 2');
        $question2->setQuiz($this->quiz);
        $question2->setChoices(['Answer 3', 'Answer 4']);
        $question2->setCorrectChoice(0);
        $this->quiz->addQuestion($question2);

        $correctAnswer2 = new Answer();
        $correctAnswer2->setText('Correct Answer 2');
        $correctAnswer2->setIsCorrect(true);
        $correctAnswer2->setSelectedChoice(0);
        $correctAnswer2->setQuestion($question2);
        $question2->addAnswer($correctAnswer2);

        $wrongAnswer2 = new Answer();
        $wrongAnswer2->setText('Wrong Answer 2');
        $wrongAnswer2->setIsCorrect(false);
        $wrongAnswer2->setSelectedChoice(1);
        $wrongAnswer2->setQuestion($question2);
        $question2->addAnswer($wrongAnswer2);

        $this->entityManager->persist($this->quiz);
        $this->entityManager->persist($question1);
        $this->entityManager->persist($question2);
        $this->entityManager->persist($correctAnswer1);
        $this->entityManager->persist($wrongAnswer1);
        $this->entityManager->persist($correctAnswer2);
        $this->entityManager->persist($wrongAnswer2);
        $this->entityManager->flush();

        // Rafraîchir les entités pour s'assurer que les relations sont chargées
        $this->entityManager->refresh($this->quiz);
        $this->entityManager->refresh($question1);
        $this->entityManager->refresh($question2);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if ($this->entityManager) {
            if ($this->quiz) {
                try {
                    // Rafraîchir le quiz et ses relations
                    $this->entityManager->refresh($this->quiz);
                    
                    foreach ($this->quiz->getQuestions() as $question) {
                        $this->entityManager->refresh($question);
                        foreach ($question->getAnswers() as $answer) {
                            $this->entityManager->refresh($answer);
                            $this->entityManager->remove($answer);
                        }
                        $this->entityManager->remove($question);
                        $this->entityManager->flush();
                    }
                    
                    // Supprimer les résultats du quiz
                    $results = $this->entityManager->getRepository(QuizResult::class)
                        ->findBy(['quiz' => $this->quiz]);
                    foreach ($results as $result) {
                        $this->entityManager->remove($result);
                    }
                    
                    $this->entityManager->remove($this->quiz);
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    // Le quiz ou ses relations peuvent déjà avoir été supprimés
                }
            }
            
            if ($this->user) {
                try {
                    $this->entityManager->refresh($this->user);
                    $this->entityManager->remove($this->user);
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    // L'utilisateur peut déjà avoir été supprimé
                }
            }
            
            $this->entityManager->close();
            $this->entityManager = null;
        }
        
        $this->client = null;
    }

    public function testSubmitQuizWithPerfectScore(): void
    {
        // S'assurer que le quiz a des questions
        $this->entityManager->refresh($this->quiz);
        $questions = $this->quiz->getQuestions();
        $this->assertNotEmpty($questions, 'Le quiz doit avoir des questions');

        // Submit answers (all correct)
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

        $this->assertNotEmpty($answers, 'Il doit y avoir des réponses correctes');

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
        // S'assurer que le quiz a des questions
        $this->entityManager->refresh($this->quiz);
        $questions = $this->quiz->getQuestions();
        $this->assertNotEmpty($questions, 'Le quiz doit avoir des questions');
        $this->assertCount(2, $questions, 'Le quiz doit avoir exactement 2 questions');

        // Submit answers (one correct, one incorrect)
        $answers = [];
        
        // First question correct
        $firstQuestionAnswers = $questions[0]->getAnswers();
        $this->assertNotEmpty($firstQuestionAnswers, 'La première question doit avoir des réponses');
        foreach ($firstQuestionAnswers as $answer) {
            if ($answer->isCorrect()) {
                $answers[] = [
                    'questionId' => $questions[0]->getId(),
                    'answerId' => $answer->getId()
                ];
                break;
            }
        }

        // Second question incorrect
        $secondQuestionAnswers = $questions[1]->getAnswers();
        $this->assertNotEmpty($secondQuestionAnswers, 'La deuxième question doit avoir des réponses');
        foreach ($secondQuestionAnswers as $answer) {
            if (!$answer->isCorrect()) {
                $answers[] = [
                    'questionId' => $questions[1]->getId(),
                    'answerId' => $answer->getId()
                ];
                break;
            }
        }

        $this->assertCount(2, $answers, 'Il doit y avoir exactement 2 réponses');

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
        $result1->setCompletedAt(new \DateTimeImmutable());

        $result2 = new QuizResult();
        $result2->setUser($this->user);
        $result2->setQuiz($this->quiz);
        $result2->setScore(50);
        $result2->setCompletedAt(new \DateTimeImmutable());

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