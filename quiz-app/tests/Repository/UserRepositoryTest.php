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
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        
        // Nettoyer la base de données avant chaque test
        $this->entityManager->createQuery('DELETE FROM App\Entity\Answer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Question')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if ($this->entityManager) {
            // Nettoyer la base de données après chaque test
            $this->entityManager->createQuery('DELETE FROM App\Entity\Answer')->execute();
            $this->entityManager->createQuery('DELETE FROM App\Entity\Question')->execute();
            $this->entityManager->createQuery('DELETE FROM App\Entity\Quiz')->execute();
            $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
            $this->entityManager->flush();
            
            $this->entityManager->close();
            $this->entityManager = null;
        }
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