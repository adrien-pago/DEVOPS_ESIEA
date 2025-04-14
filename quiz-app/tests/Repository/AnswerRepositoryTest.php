<?php

namespace App\Tests\Repository;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Repository\AnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AnswerRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AnswerRepository $answerRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->answerRepository = $this->entityManager->getRepository(Answer::class);
    }

    public function testFindByQuestion(): void
    {
        // Create test data
        $quiz = new Quiz();
        $quiz->setTheme('Test Theme');
        $this->entityManager->persist($quiz);

        $question = new Question();
        $question->setQuiz($quiz);
        $question->setText('Test Question');
        $question->setChoices(['A', 'B', 'C']);
        $question->setCorrectChoice(0);
        $this->entityManager->persist($question);

        $answer1 = new Answer();
        $answer1->setQuestion($question);
        $answer1->setIsCorrect(true);
        $this->entityManager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setQuestion($question);
        $answer2->setIsCorrect(false);
        $this->entityManager->persist($answer2);

        $this->entityManager->flush();

        // Test findByQuestion
        $answers = $this->answerRepository->findByQuestion($question);
        $this->assertCount(2, $answers);
        $this->assertContains($answer1, $answers);
        $this->assertContains($answer2, $answers);

        // Cleanup
        $this->entityManager->remove($answer1);
        $this->entityManager->remove($answer2);
        $this->entityManager->remove($question);
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }

    public function testGetCorrectAnswersCount(): void
    {
        // Create test data
        $quiz = new Quiz();
        $quiz->setTheme('Test Theme');
        $this->entityManager->persist($quiz);

        $question = new Question();
        $question->setQuiz($quiz);
        $question->setText('Test Question');
        $question->setChoices(['A', 'B', 'C']);
        $question->setCorrectChoice(0);
        $this->entityManager->persist($question);

        $answer1 = new Answer();
        $answer1->setQuestion($question);
        $answer1->setIsCorrect(true);
        $this->entityManager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setQuestion($question);
        $answer2->setIsCorrect(true);
        $this->entityManager->persist($answer2);

        $answer3 = new Answer();
        $answer3->setQuestion($question);
        $answer3->setIsCorrect(false);
        $this->entityManager->persist($answer3);

        $this->entityManager->flush();

        // Test getCorrectAnswersCount
        $correctCount = $this->answerRepository->getCorrectAnswersCount($question);
        $this->assertEquals(2, $correctCount);

        // Cleanup
        $this->entityManager->remove($answer1);
        $this->entityManager->remove($answer2);
        $this->entityManager->remove($answer3);
        $this->entityManager->remove($question);
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }
} 