<?php

namespace App\Tests\Repository;

use App\Entity\Quiz;
use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuizRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $quizRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->quizRepository = $this->entityManager->getRepository(Quiz::class);
    }

    public function testFindByTheme(): void
    {
        // Créer un quiz de test
        $quiz = new Quiz();
        $quiz->setTheme('Géographie');
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Rechercher le quiz par thème
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
        $moderatedQuizzes = $this->quizRepository->findModerated();
        
        $this->assertCount(1, $moderatedQuizzes);
        $this->assertTrue($moderatedQuizzes[0]->isModerated());

        // Nettoyer
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }
} 