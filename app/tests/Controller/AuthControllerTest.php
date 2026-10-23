<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use DateTime;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends ApiTestCase
{
    public function testRegister(): void
    {
        $member = $this->createMember(['email' => 'ivan@example.com']);

        $data = $this->requestApi('POST', '/register', [
            'email' => 'ivan@example.com',
            'password' => 'secret123',
        ]);

        $this->assertStatusCode(201);
        $this->assertSame($member->getId(), $data['id']);
        $this->assertSame('ivan@example.com', $data['email']);
        $this->assertTrue($data['registered']);
        $this->assertArrayNotHasKey('password', $data);

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($this->reloadMember($member->getId()), 'secret123'));
    }

    public function testAuth(): void
    {
        $member = $this->createMember(['email' => 'ivan@example.com', 'password' => 'secret123']);

        $data = $this->requestApi('POST', '/auth', [
            'email' => 'ivan@example.com',
            'password' => 'secret123',
        ]);

        $this->assertStatusCode(200);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $data['token']);

        $member = $this->reloadMember($member->getId());
        $this->assertSame($data['token'], $member->getApiToken());
        $this->assertGreaterThan(new DateTime(), $member->getApiTokenExpiresAt());
    }
}
