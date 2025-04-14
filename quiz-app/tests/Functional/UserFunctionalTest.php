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
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
            'username' => 'testuser'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);

        // Vérifier que l'utilisateur a été créé en base de données
        $user = $this->entityManager->getRepository(User::class)->find($response['id']);
        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('testuser', $user->getUsername());
    }

    public function testUserLogin(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Tester la connexion
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
    }

    public function testProtectedRoute(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Tester l'accès à une route protégée sans authentification
        $this->client->request('GET', '/api/user/profile');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Se connecter
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $token = $response['token'];

        // Tester l'accès à une route protégée avec authentification
        $this->client->request('GET', '/api/user/profile', [], [], ['HTTP_Authorization' => 'Bearer ' . $token]);
        $this->assertResponseIsSuccessful();
    }

    public function testUserProfileUpdate(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Se connecter
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $token = $response['token'];

        // Mettre à jour le profil
        $this->client->request('PUT', '/api/user/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], json_encode([
            'username' => 'updatedusername',
            'email' => 'updated@example.com'
        ]));

        $this->assertResponseIsSuccessful();
        
        // Vérifier que les modifications ont été appliquées
        $this->entityManager->refresh($user);
        $this->assertEquals('updatedusername', $user->getUsername());
        $this->assertEquals('updated@example.com', $user->getEmail());
    }
} 