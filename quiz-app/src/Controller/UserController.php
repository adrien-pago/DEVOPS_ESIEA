<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\QuizResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $security;
    private $jwtManager;
    private $params;
    private $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        JWTTokenManagerInterface $jwtManager,
        ParameterBagInterface $params,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->security = $security;
        $this->jwtManager = $jwtManager;
        $this->params = $params;
        $this->tokenStorage = $tokenStorage;
    }

    private function generateToken(User $user): string
    {
        try {
            // Essayer d'utiliser JWT
            return $this->jwtManager->create($user);
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser un token simple
            return base64_encode(json_encode([
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]));
        }
    }

    #[Route('/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->generateToken($user);

        return $this->json([
            'id' => $user->getId(),
            'token' => $token
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'user_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->generateToken($user);
        
        return $this->json(['token' => $token]);
    }

    #[Route('/user/profile', name: 'user_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->json($user);
    }

    #[Route('/user/profile', name: 'user_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        $this->entityManager->flush();
        
        return $this->json($user);
    }

    #[Route('/user/quiz-history', name: 'user_quiz_history', methods: ['GET'])]
    public function quizHistory(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $results = $this->entityManager->getRepository(QuizResult::class)->findBy(['user' => $user]);
        
        return $this->json($results);
    }
} 