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
    }

    public function testUserCreation(): void
    {
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertEmpty($this->user->getQuizzes());
    }

    public function testUserUsername(): void
    {
        $username = 'testuser';
        $this->user->setUsername($username);
        $this->assertEquals($username, $this->user->getUsername());
    }

    public function testUserPassword(): void
    {
        $password = 'password123';
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testUserQuizzes(): void
    {
        $quiz = new Quiz();
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