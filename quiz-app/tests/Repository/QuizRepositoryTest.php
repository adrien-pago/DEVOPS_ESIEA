<?php

namespace App\Tests\Repository;

use App\Entity\Quiz;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuizRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private QuizRepository $quizRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->quizRepository = $this->entityManager->getRepository(Quiz::class);
    }

    public function testFindByTheme(): void
    {
        // Créer un quiz
        $quiz = new Quiz();
        $quiz->setTheme('Géographie');
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Rechercher les quiz par thème
        $foundQuizzes = $this->quizRepository->findByTheme('Géographie');
        
        $this->assertCount(1, $foundQuizzes);
        $this->assertEquals('Géographie', $foundQuizzes[0]->getTheme());

        // Nettoyer
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }

    public function testFindModerated(): void
    {
        // Créer un quiz modéré
        $quiz = new Quiz();
        $quiz->setTheme('Histoire');
        $quiz->setModerated(true);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Rechercher les quiz modérés
        $foundQuizzes = $this->quizRepository->findModerated();
        
        $this->assertCount(1, $foundQuizzes);
        $this->assertTrue($foundQuizzes[0]->isModerated());

        // Nettoyer
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }
} 