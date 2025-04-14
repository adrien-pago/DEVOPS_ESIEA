<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\QuizResult;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/quiz')]
class QuizController extends AbstractController
{
    private $entityManager;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('', name: 'quiz_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $quiz = new Quiz();
        $quiz->setTitle($data['title']);
        $quiz->setTheme($data['theme']);
        $quiz->setModerated(false);

        foreach ($data['questions'] as $questionData) {
            $question = new Question();
            $question->setText($questionData['text']);
            $question->setQuiz($quiz);

            foreach ($questionData['answers'] as $answerData) {
                $answer = new Answer();
                $answer->setText($answerData['text']);
                $answer->setIsCorrect($answerData['isCorrect']);
                $answer->setQuestion($question);
            }
        }

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        return $this->json(['id' => $quiz->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): JsonResponse
    {
        return $this->json($quiz);
    }

    #[Route('/{id}/moderate', name: 'quiz_moderate', methods: ['PUT'])]
    public function moderate(Quiz $quiz): JsonResponse
    {
        $quiz->setModerated(true);
        $this->entityManager->flush();

        return $this->json(['message' => 'Quiz moderated successfully']);
    }

    #[Route('/{id}/submit', name: 'quiz_submit', methods: ['POST'])]
    public function submit(Request $request, Quiz $quiz): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $answers = $data['answers'];
        
        $totalQuestions = count($quiz->getQuestions());
        $correctAnswers = 0;

        foreach ($answers as $answerData) {
            $question = $this->entityManager->getRepository(Question::class)->find($answerData['questionId']);
            $answer = $this->entityManager->getRepository(Answer::class)->find($answerData['answerId']);

            if ($answer && $answer->isCorrect()) {
                $correctAnswers++;
            }
        }

        $score = ($correctAnswers / $totalQuestions) * 100;

        // Enregistrer le résultat
        $result = new QuizResult();
        $result->setUser($this->getUser());
        $result->setQuiz($quiz);
        $result->setScore($score);
        $result->setCompletedAt(new \DateTimeImmutable());

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return $this->json([
            'score' => $score,
            'correctAnswers' => $correctAnswers,
            'totalQuestions' => $totalQuestions
        ]);
    }
} 