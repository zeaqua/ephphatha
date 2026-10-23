<?php

namespace App\Tests;

use App\Entity\Member;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Базовий клас для тестів API: запити йдуть напряму в ядро Symfony,
 * а перед кожним тестом схема тестової бази створюється заново.
 */
abstract class ApiTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected ?Response $response = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function createMember(array $overrides = []): Member
    {
        $member = new Member();
        $member
            ->setName($overrides['name'] ?? 'Тестовий Член')
            ->setBirthDate(new DateTime($overrides['birth_date'] ?? '1990-01-01'))
            ->setPhone($overrides['phone'] ?? '+380501234567')
            ->setEmail($overrides['email'] ?? null)
            ->setChurchRole($overrides['role'] ?? Member::ROLE_CHURCH_MEMBER)
            ->setActive(1)
            ->setLastUpdate(new DateTime());

        if (isset($overrides['password'])) {
            $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
            $member->setPassword($hasher->hashPassword($member, $overrides['password']));
        }

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * @return array{Member, string} член з дійсним API-токеном і сам токен
     */
    protected function createAuthenticatedMember(): array
    {
        $token = bin2hex(random_bytes(32));

        $member = $this->createMember(['name' => 'Адмін', 'email' => 'admin@example.com', 'password' => 'secret']);
        $member->setApiToken($token);
        $member->setApiTokenExpiresAt(new DateTime('+1 month'));
        $this->entityManager->flush();

        return [$member, $token];
    }

    protected function requestApi(string $method, string $uri, array $params = [], ?string $token = null): mixed
    {
        $server = $token !== null ? ['HTTP_AUTHORIZATION' => 'Bearer ' . $token] : [];

        $request = Request::create($uri, $method, $params, [], [], $server);
        $this->response = self::$kernel->handle($request);
        self::$kernel->terminate($request, $this->response);

        return json_decode($this->response->getContent(), true);
    }

    protected function assertStatusCode(int $expected): void
    {
        $this->assertSame($expected, $this->response->getStatusCode(), $this->response->getContent());
    }

    /**
     * Перечитує члена з бази, минаючи кеш EntityManager.
     */
    protected function reloadMember(int $id): Member
    {
        $this->entityManager->clear();

        return $this->entityManager->find(Member::class, $id);
    }
}
