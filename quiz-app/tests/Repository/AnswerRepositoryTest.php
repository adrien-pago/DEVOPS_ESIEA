<?php

namespace App\Tests\Repository;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\AnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AnswerRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private AnswerRepository $answerRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->answerRepository = $this->entityManager->getRepository(Answer::class);
        
        // Clean up the database in the correct order
        $this->entityManager->createQuery('DELETE FROM App\Entity\QuizResult')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Answer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Question')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testFindByQuestion(): void
    {
        // Create a user first
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create test data
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setAuthor($user);
        $this->entityManager->persist($quiz);

        $question = new Question();
        $question->setQuiz($quiz);
        $question->setText('Test Question');
        $question->setChoices(['A', 'B', 'C']);
        $question->setCorrectChoice(0);
        $this->entityManager->persist($question);

        $answer1 = new Answer();
        $answer1->setQuestion($question);
        $answer1->setIsCorrect(true);
        $answer1->setText('Answer 1');
        $answer1->setSelectedChoice(0);
        $this->entityManager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setQuestion($question);
        $answer2->setIsCorrect(false);
        $answer2->setText('Answer 2');
        $answer2->setSelectedChoice(1);
        $this->entityManager->persist($answer2);

        $this->entityManager->flush();

        // Test findByQuestion
        $answers = $this->answerRepository->findByQuestion($question);
        $this->assertCount(2, $answers);
        $this->assertContains($answer1, $answers);
        $this->assertContains($answer2, $answers);
    }

    public function testGetCorrectAnswersCount(): void
    {
        // Create a user first
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create test data
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setTheme('Test Theme');
        $quiz->setAuthor($user);
        $this->entityManager->persist($quiz);

        $question = new Question();
        $question->setQuiz($quiz);
        $question->setText('Test Question');
        $question->setChoices(['A', 'B', 'C']);
        $question->setCorrectChoice(0);
        $this->entityManager->persist($question);

        $answer1 = new Answer();
        $answer1->setQuestion($question);
        $answer1->setIsCorrect(true);
        $answer1->setText('Answer 1');
        $answer1->setSelectedChoice(0);
        $this->entityManager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setQuestion($question);
        $answer2->setIsCorrect(true);
        $answer2->setText('Answer 2');
        $answer2->setSelectedChoice(1);
        $this->entityManager->persist($answer2);

        $answer3 = new Answer();
        $answer3->setQuestion($question);
        $answer3->setIsCorrect(false);
        $answer3->setText('Answer 3');
        $answer3->setSelectedChoice(2);
        $this->entityManager->persist($answer3);

        $this->entityManager->flush();

        // Test getCorrectAnswersCount
        $correctCount = $this->answerRepository->getCorrectAnswersCount($question);
        $this->assertEquals(2, $correctCount);
    }

    protected function tearDown(): void
    {
        // Clean up the database in the correct order
        $this->entityManager->createQuery('DELETE FROM App\Entity\QuizResult')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Answer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Question')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        parent::tearDown();
        
        // Close the entity manager
        $this->entityManager->close();
        $this->entityManager = null;
    }
} 