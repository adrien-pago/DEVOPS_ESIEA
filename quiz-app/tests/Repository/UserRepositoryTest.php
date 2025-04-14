<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
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