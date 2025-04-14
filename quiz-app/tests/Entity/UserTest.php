<?php

namespace App\Tests\Entity;

use App\Entity\Quiz;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('testuser');
        $this->user->setEmail('testuser@example.com');
        $this->user->setPassword('password123');
    }

    public function testUserCreation(): void
    {
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertEmpty($this->user->getQuizzes());
        $this->assertEquals('testuser', $this->user->getUsername());
        $this->assertEquals('testuser@example.com', $this->user->getEmail());
    }

    public function testUserUsername(): void
    {
        $username = 'newuser';
        $this->user->setUsername($username);
        $this->assertEquals($username, $this->user->getUsername());
    }

    public function testUserPassword(): void
    {
        $password = 'newpassword';
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testUserQuizzes(): void
    {
        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz Title');
        $quiz->setTheme('Test Theme');
        
        $this->user->addQuiz($quiz);
        
        $this->assertCount(1, $this->user->getQuizzes());
        $this->assertTrue($this->user->getQuizzes()->contains($quiz));
        $this->assertEquals($this->user, $quiz->getCreator());
        
        $this->user->removeQuiz($quiz);
        
        $this->assertCount(0, $this->user->getQuizzes());
        $this->assertFalse($this->user->getQuizzes()->contains($quiz));
        $this->assertNull($quiz->getCreator());
    }
} 