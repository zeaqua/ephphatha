<?php

namespace App\Controller;

use App\Entity\Member;
use App\Repository\MemberRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = $request->request->all();

        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        if ($email === '' || $password === '') {
            return $this->json(['error' => 'Fields "email" and "password" are required'], 422);
        }

        /** @var Member|null $user */
        $user = $entityManager->getRepository(Member::class)->findOneBy(['email' => $email]);

        if (empty($user)) {
            return $this->json(['error' => 'Unknown email'], 404);
        }

        if (!empty($user->getPassword())) {
            return $this->json(['error' => 'Member is already registered'], 409);
        }

        $hash = $hasher->hashPassword($user, $password);
        $user->setPassword($hash);

        $entityManager->flush();

        // 7) Відповідь без пароля
        return $this->json([
            'id'         => $user->getId(),
            'email'      => $user->getEmail(),
            'name'       => $user->getName(),
            'birthDate'  => $user->getBirthDate()->format('Y-m-d'),
            'baptDate'   => $user->getBaptDate()?->format('Y-m-d'),
            'active'     => $user->getActive(),
            'registered' => true,
        ], 201);
    }

    #[Route('/auth', name: 'auth', methods: ['POST'])]
    public function auth(
        Request $request,
        MemberRepository $members,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = $request->request->all();

        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            return $this->json(['error' => 'email and password are required'], 422);
        }

        /** @var Member|null $user */
        $user = $members->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$hasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = bin2hex(random_bytes(32));

        $user->setApiToken($token);
        $user->setApiTokenExpiresAt((new DateTime('+1 month')));

        $entityManager->flush();

        return $this->json(['token' => $token]);
    }
}
