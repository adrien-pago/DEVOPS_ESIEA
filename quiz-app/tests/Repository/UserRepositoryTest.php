<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
        
        // Clean up the database in the correct order
        $this->entityManager->createQuery('DELETE FROM App\Entity\QuizResult')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Answer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Question')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
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

    public function testFindByUsername(): void
    {
        // Create a test user
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('testuser@example.com');
        $user->setPassword('password123');

        // Persist the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Find the user by username
        $foundUser = $this->userRepository->findByUsername('testuser');

        // Assert that the user was found
        $this->assertNotNull($foundUser);
        $this->assertEquals('testuser', $foundUser->getUsername());

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindByUsernameNotFound(): void
    {
        // Try to find a non-existent user
        $foundUser = $this->userRepository->findByUsername('nonexistentuser');

        // Assert that no user was found
        $this->assertNull($foundUser);
    }
} 