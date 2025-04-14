<?php

namespace App\Tests\Repository;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuestionRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $questionRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->questionRepository = $this->entityManager->getRepository(Question::class);
    }

    public function testFindByQuiz(): void
    {
        // Créer un quiz
        $quiz = new Quiz();
        $quiz->setTheme('Mathématiques');
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Créer une question associée au quiz
        $question = new Question();
        $question->setText('Quelle est la somme de 2 + 2 ?');
        $question->setChoices(['3', '4', '5', '6']);
        $question->setCorrectChoice(1);
        $question->setQuiz($quiz);
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        // Rechercher les questions par quiz
        $foundQuestions = $this->questionRepository->findByQuiz($quiz);
        
        $this->assertCount(1, $foundQuestions);
        $this->assertEquals($quiz, $foundQuestions[0]->getQuiz());

        // Nettoyer
        $this->entityManager->remove($question);
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }

    public function testFindRandomQuestions(): void
    {
        // Créer un quiz
        $quiz = new Quiz();
        $quiz->setTheme('Histoire');
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        // Créer plusieurs questions
        $questions = [];
        for ($i = 1; $i <= 5; $i++) {
            $question = new Question();
            $question->setText("Question $i");
            $question->setChoices(['A', 'B', 'C', 'D']);
            $question->setCorrectChoice(0);
            $question->setQuiz($quiz);
            $this->entityManager->persist($question);
            $questions[] = $question;
        }
        $this->entityManager->flush();

        // Rechercher 3 questions aléatoires
        $randomQuestions = $this->questionRepository->findRandomQuestions($quiz, 3);
        
        $this->assertCount(3, $randomQuestions);
        $this->assertNotEquals($randomQuestions[0], $randomQuestions[1]);
        $this->assertNotEquals($randomQuestions[1], $randomQuestions[2]);

        // Nettoyer
        foreach ($questions as $question) {
            $this->entityManager->remove($question);
        }
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }
} 