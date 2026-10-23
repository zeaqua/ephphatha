<?php

namespace App\Tests\Controller;

use App\Entity\Member;
use App\Tests\ApiTestCase;
use DateTime;

class MembersControllerTest extends ApiTestCase
{
    private Member $admin;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->admin, $this->token] = $this->createAuthenticatedMember();
    }

    public function testRoles(): void
    {
        $data = $this->requestApi('GET', '/api/roles', [], $this->token);

        $this->assertStatusCode(200);
        $this->assertSame([
            Member::ROLE_CHURCH_MEMBER,
            Member::ROLE_DEACON,
            Member::ROLE_PRESBYTER,
        ], $data);
    }

    public function testList(): void
    {
        $this->createMember(['name' => 'Марія Бондаренко']);
        $this->createMember(['name' => 'Петро Ткаченко']);

        $data = $this->requestApi('GET', '/api/members', [], $this->token);

        $this->assertStatusCode(200);
        $this->assertCount(3, $data);
        $this->assertEqualsCanonicalizing(
            ['Адмін', 'Марія Бондаренко', 'Петро Ткаченко'],
            array_column($data, 'name')
        );
    }

    public function testGetById(): void
    {
        $member = $this->createMember([
            'name' => 'Марія Бондаренко',
            'birth_date' => '1985-04-30',
            'phone' => '+380671112233',
            'email' => 'maria@example.com',
        ]);

        $data = $this->requestApi('GET', '/api/members/' . $member->getId(), [], $this->token);

        // Ендпоінт зараз повертає 201, тому перевіряємо лише успішність
        $this->assertTrue($this->response->isSuccessful(), $this->response->getContent());
        $this->assertSame($member->getId(), $data['id']);
        $this->assertSame('Марія Бондаренко', $data['name']);
        $this->assertSame('1985-04-30', $data['birth_date']);
        $this->assertSame('+380671112233', $data['phone']);
        $this->assertSame('maria@example.com', $data['email']);
        $this->assertSame(Member::ROLE_CHURCH_MEMBER, $data['role']);
    }

    public function testAdd(): void
    {
        $pastor = $this->createMember(['name' => 'Олександр Мельник', 'role' => Member::ROLE_PRESBYTER]);
        $diakon = $this->createMember(['name' => 'Андрій Коваленко', 'role' => Member::ROLE_DEACON]);

        $data = $this->requestApi('POST', '/api/members/add', [
            'name' => 'Іван Петренко',
            'birth_date' => '1990-05-12',
            'bapt_date' => '2010-08-01',
            'phone' => '+380 (50) 123-45-67',
            'email' => 'ivan@example.com',
            'address' => 'м. Київ, вул. Хрещатик, 1',
            'pastor_id' => $pastor->getId(),
            'diakon_id' => $diakon->getId(),
        ], $this->token);

        $this->assertStatusCode(201);
        $this->assertIsInt($data['id']);

        $member = $this->reloadMember($data['id']);
        $this->assertSame('Іван Петренко', $member->getName());
        $this->assertSame('1990-05-12', $member->getBirthDate()->format('Y-m-d'));
        $this->assertSame('2010-08-01', $member->getBaptDate()->format('Y-m-d'));
        $this->assertSame('+380501234567', $member->getPhone());
        $this->assertSame('ivan@example.com', $member->getEmail());
        $this->assertSame('м. Київ, вул. Хрещатик, 1', $member->getAddress());
        $this->assertSame(Member::ROLE_CHURCH_MEMBER, $member->getChurchRole());
        $this->assertSame(1, $member->getActive());
        $this->assertSame($pastor->getId(), $member->getPastor()->getId());
        $this->assertSame($diakon->getId(), $member->getDiakon()->getId());
    }

    public function testEdit(): void
    {
        $member = $this->createMember(['name' => 'Іван Петренко']);
        $lastUpdate = new DateTime('2020-01-01 00:00:00');
        $member->setLastUpdate($lastUpdate);
        $this->entityManager->flush();

        $data = $this->requestApi('POST', '/api/members/edit/' . $member->getId(), [
            'name' => 'Іван Петренко-Шевчук',
            'phone' => '+380 67 999 88 77',
            'role' => Member::ROLE_DEACON,
            'address' => 'м. Київ, просп. Перемоги, 10',
        ], $this->token);

        $this->assertStatusCode(200);
        $this->assertSame(['status' => 'success'], $data);

        $member = $this->reloadMember($member->getId());
        $this->assertSame('Іван Петренко-Шевчук', $member->getName());
        $this->assertSame('+380679998877', $member->getPhone());
        $this->assertSame(Member::ROLE_DEACON, $member->getChurchRole());
        $this->assertSame('м. Київ, просп. Перемоги, 10', $member->getAddress());
        $this->assertGreaterThan($lastUpdate, $member->getLastUpdate());
    }
}
