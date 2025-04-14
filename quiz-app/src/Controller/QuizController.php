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
        $quiz->setAuthor($this->getUser());

        foreach ($data['questions'] as $questionData) {
            $question = new Question();
            $question->setText($questionData['text']);
            $quiz->addQuestion($question);

            foreach ($questionData['answers'] as $index => $answerData) {
                $answer = new Answer();
                $answer->setText($answerData['text']);
                $answer->setIsCorrect($answerData['isCorrect']);
                $answer->setSelectedChoice($index);
                $question->addAnswer($answer);
                $this->entityManager->persist($answer);
            }
            $this->entityManager->persist($question);
        }

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        return $this->json($quiz, Response::HTTP_CREATED, [], ['groups' => ['quiz:read']]);
    }

    #[Route('', name: 'quiz_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('q')
               ->from(Quiz::class, 'q')
               ->where('q.author IS NOT NULL');
            
            $quizzes = $qb->getQuery()->getResult();
            return $this->json($quizzes, Response::HTTP_OK, [], ['groups' => ['quiz:read']]);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return $this->json(['message' => 'Some quizzes have authors that no longer exist'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): JsonResponse
    {
        try {
            return $this->json($quiz, Response::HTTP_OK, [], ['groups' => ['quiz:read']]);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return $this->json(['message' => 'Quiz not found or author no longer exists'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}', name: 'quiz_update', methods: ['PUT'])]
    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        try {
            // Check if the current user is the author of the quiz
            if ($quiz->getAuthor() !== $this->getUser()) {
                return $this->json(['message' => 'You are not authorized to update this quiz'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['title'])) {
                $quiz->setTitle($data['title']);
            }
            
            if (isset($data['theme'])) {
                $quiz->setTheme($data['theme']);
            }
            
            $this->entityManager->flush();
            
            return $this->json($quiz, Response::HTTP_OK, [], ['groups' => ['quiz:read']]);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return $this->json(['message' => 'Quiz not found or author no longer exists'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}', name: 'quiz_delete', methods: ['DELETE'])]
    public function delete(Quiz $quiz): JsonResponse
    {
        try {
            // Check if the current user is the author of the quiz
            if ($quiz->getAuthor() !== $this->getUser()) {
                return $this->json(['message' => 'You are not authorized to delete this quiz'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($quiz);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return $this->json(['message' => 'Quiz not found or author no longer exists'], Response::HTTP_NOT_FOUND);
        }
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
        if ($totalQuestions === 0) {
            return $this->json([
                'error' => 'Cannot submit answers for a quiz with no questions',
            ], Response::HTTP_BAD_REQUEST);
        }

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