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

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->questionRepository = $this->entityManager->getRepository(Question::class);
        
        // Clean up the database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Question')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testFindByQuiz(): void
    {
        // Create a user first
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Créer un quiz
        $quiz = new Quiz();
        $quiz->setTheme('Mathématiques');
        $quiz->setCreator($user);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Créer une question associée au quiz
        $question = new Question();
        $question->setText('Quelle est la somme de 2 + 2 ?');
        $question->setChoices(['3', '4', '5', '6']);
        $question->setCorrectChoice(1);
        $question->setQuiz($quiz);
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        // Rechercher les questions par quiz
        $foundQuestions = $this->questionRepository->findByQuiz($quiz);
        
        $this->assertCount(1, $foundQuestions);
        $this->assertEquals($quiz, $foundQuestions[0]->getQuiz());
    }

    public function testFindRandomQuestions(): void
    {
        // Create a user first
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Créer un quiz
        $quiz = new Quiz();
        $quiz->setTheme('Histoire');
        $quiz->setCreator($user);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Créer plusieurs questions
        $questions = [];
        for ($i = 1; $i <= 5; $i++) {
            $question = new Question();
            $question->setText("Question $i");
            $question->setChoices(['A', 'B', 'C', 'D']);
            $question->setCorrectChoice(0);
            $question->setQuiz($quiz);
            $this->entityManager->persist($question);
            $questions[] = $question;
        }
        $this->entityManager->flush();

        // Rechercher 3 questions aléatoires
        $randomQuestions = $this->questionRepository->findRandomQuestions($quiz, 3);
        
        $this->assertCount(3, $randomQuestions);
        $this->assertNotEquals($randomQuestions[0], $randomQuestions[1]);
        $this->assertNotEquals($randomQuestions[1], $randomQuestions[2]);
    }

    protected function tearDown(): void
    {
        // Clean up the database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Question')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        parent::tearDown();
        
        // Close the entity manager
        $this->entityManager->close();
        $this->entityManager = null;
    }
} 