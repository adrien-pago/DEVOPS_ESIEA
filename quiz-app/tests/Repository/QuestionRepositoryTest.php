<?php

namespace App\Tests\Repository;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuestionRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private QuestionRepository $questionRepository;
    private User $user;
    private Quiz $quiz;
    private Question $question;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->questionRepository = $this->entityManager->getRepository(Question::class);

        // Create test user
        $this->user = new User();
        $this->user->setEmail('question_test@example.com');
        $this->user->setUsername('questionuser_test');
        $this->user->setPassword('password123');
        $this->entityManager->persist($this->user);

        // Create test quiz
        $this->quiz = new Quiz();
        $this->quiz->setTitle('Test Quiz for Questions');
        $this->quiz->setTheme('Test Theme');
        $this->quiz->setModerated(true);
        $this->quiz->setAuthor($this->user);
        $this->entityManager->persist($this->quiz);

        // Create test questions
        $this->question = new Question();
        $this->question->setText('Test Question');
        $this->question->setQuiz($this->quiz);
        $this->question->setChoices(['A', 'B', 'C']);
        $this->question->setCorrectChoice(0);
        $this->entityManager->persist($this->question);

        $this->entityManager->flush();
    }

    public function testFindByQuiz(): void
    {
        // Rechercher les questions par quiz
        $foundQuestions = $this->questionRepository->findByQuiz($this->quiz);
        
        $this->assertCount(1, $foundQuestions);
        $this->assertEquals($this->quiz, $foundQuestions[0]->getQuiz());
    }

    public function testFindRandomQuestions(): void
    {
        // Create additional questions for random test
        for ($i = 0; $i < 5; $i++) {
            $question = new Question();
            $question->setText('Test Question ' . $i);
            $question->setQuiz($this->quiz);
            $question->setChoices(['A', 'B', 'C']);
            $question->setCorrectChoice(0);
            $this->entityManager->persist($question);
        }
        $this->entityManager->flush();

        // Rechercher 3 questions aléatoires
        $randomQuestions = $this->questionRepository->findRandomQuestions($this->quiz, 3);
        
        $this->assertCount(3, $randomQuestions);
        foreach ($randomQuestions as $question) {
            $this->assertEquals($this->quiz, $question->getQuiz());
        }
    }

    protected function tearDown(): void
    {
        if ($this->question) {
            $this->entityManager->remove($this->question);
        }
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
    }
} 