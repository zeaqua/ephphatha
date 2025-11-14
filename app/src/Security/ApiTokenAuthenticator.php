<?php

namespace App\Security;

use App\Repository\MemberRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return false;
        }

        $auth = $request->headers->get('Authorization', '');

        return str_starts_with($auth, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        $token = explode(' ', $authHeader)[1] ?? '';

        if ($token === '') {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $userBadge = new UserBadge($token, fn ($token) => $this->loadUserByToken($token));

        return new SelfValidatingPassport($userBadge);
    }

    public function loadUserByToken(string $token): UserInterface
    {
        $user = $this->members->findOneBy(['apiToken' => $token]);
        if (empty($user)) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        $expiresAt = $user->getApiTokenExpiresAt();
        if ($expiresAt !== null && $expiresAt < new DateTime('now')) {
            throw new CustomUserMessageAuthenticationException('Token expired');
        }

        $user->setApiTokenExpiresAt((new DateTime('+1 month')));

        $this->entityManager->flush();

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, \Throwable $exception): ?JsonResponse
    {
        $data = ['error' => $exception->getMessage()];

        return new JsonResponse($data, 401);
    }
}
