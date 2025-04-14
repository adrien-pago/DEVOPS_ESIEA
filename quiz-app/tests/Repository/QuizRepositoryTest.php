<?php

namespace App\Tests\Repository;

use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuizRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private QuizRepository $quizRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->quizRepository = $this->entityManager->getRepository(Quiz::class);
        
        // Ensure we're starting with a clean database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testFindByTheme()
    {
        // Create a user first
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $quiz = new Quiz();
        $quiz->setTheme('Géographie');
        $quiz->setCreator($user);
        $quiz->setModerated(true);

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $foundQuiz = $this->quizRepository->findByTheme('Géographie');
        $this->assertCount(1, $foundQuiz);
        $this->assertEquals('Géographie', $foundQuiz[0]->getTheme());
    }

    public function testFindModerated()
    {
        // Create a user first
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $quiz = new Quiz();
        $quiz->setTheme('Histoire');
        $quiz->setCreator($user);
        $quiz->setModerated(true);

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $moderatedQuizzes = $this->quizRepository->findModerated();
        $this->assertCount(1, $moderatedQuizzes);
        $this->assertTrue($moderatedQuizzes[0]->isModerated());
    }

    protected function tearDown(): void
    {
        // Clean up the database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        parent::tearDown();
        
        // Close the entity manager
        $this->entityManager->close();
        $this->entityManager = null;
    }
} 