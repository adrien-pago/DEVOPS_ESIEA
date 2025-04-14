<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFunctionalTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
    }

    public function testUserRegistration(): void
    {
        $userData = [
            'email' => 'register_test@example.com',
            'username' => 'registeruser',
            'password' => 'password123'
        ];

        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($userData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
    }

    public function testUserLogin(): void
    {
        // Create a user first
        $user = new User();
        $user->setEmail('login_test@example.com');
        $user->setUsername('loginuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Set up basic auth
        $this->client->setServerParameters([
            'PHP_AUTH_USER' => 'login_test@example.com',
            'PHP_AUTH_PW' => 'password123'
        ]);

        // Try to access a protected route
        $this->client->request('GET', '/api/user/profile');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('email', $response);
        $this->assertEquals('login_test@example.com', $response['email']);

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testProtectedRoute(): void
    {
        // Create a user first
        $user = new User();
        $user->setEmail('protected_test@example.com');
        $user->setUsername('protecteduser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Set up basic auth
        $this->client->setServerParameters([
            'PHP_AUTH_USER' => 'protected_test@example.com',
            'PHP_AUTH_PW' => 'password123'
        ]);

        $this->client->request('GET', '/api/user/profile');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('email', $response);

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testUserProfileUpdate(): void
    {
        // Create a user first
        $user = new User();
        $user->setEmail('update_test@example.com');
        $user->setUsername('updateuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Set up basic auth
        $this->client->setServerParameters([
            'PHP_AUTH_USER' => 'update_test@example.com',
            'PHP_AUTH_PW' => 'password123'
        ]);

        $updateData = [
            'username' => 'updateduser'
        ];

        $this->client->request('PUT', '/api/user/profile', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($updateData));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('updateduser', $response['username']);

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
} 