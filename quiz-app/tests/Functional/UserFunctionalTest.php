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
            'email' => 'register@example.com',
            'password' => 'password123',
            'username' => 'testuser'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertEquals('register@example.com', $response['email']);
    }

    public function testUserLogin(): void
    {
        // Créer un utilisateur pour le test
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'login@example.com',
            'password' => 'password123',
            'username' => 'loginuser'
        ]));

        // Tenter de se connecter
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'login@example.com',
            'password' => 'password123'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
    }

    public function testProtectedRoute(): void
    {
        // Créer un utilisateur et obtenir un token
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'protected@example.com',
            'password' => 'password123',
            'username' => 'protecteduser'
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $token = $response['token'];

        // Accéder à une route protégée
        $this->client->request('GET', '/api/user/profile', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUserProfileUpdate(): void
    {
        // Créer un utilisateur et obtenir un token
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'update@example.com',
            'password' => 'password123',
            'username' => 'updateuser'
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $token = $response['token'];

        // Mettre à jour le profil
        $this->client->request('PUT', '/api/user/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], json_encode([
            'username' => 'updatedusername'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('updatedusername', $response['username']);
    }
} 