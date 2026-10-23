<?php

namespace App\Controller;

use App\Entity\Member;
use App\Repository\MemberRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class MembersController extends AbstractController
{
    private const REQUIRED_FIELDS = ['name', 'birth_date', 'phone'];

    #[Route('/api/roles', name: 'app_roles', methods: ['GET'])]
    public function getRoles(): JsonResponse
    {
        return $this->json([
            Member::ROLE_CHURCH_MEMBER,
            Member::ROLE_DEACON,
            Member::ROLE_PRESBYTER,
        ]);
    }

    #[Route('/api/members', name: 'app_member_index', methods: ['GET'])]
    public function list(MemberRepository $memberRepository): JsonResponse
    {
        $members = $memberRepository->findAll();

        return $this->json(
            array_map(fn(Member $member) => $member->toArray(), $members)
        );
    }

    #[Route('/api/members/{id}', methods: ['GET'])]
    public function getById(Member $member): jsonResponse
    {
        return $this->json($member->toArray(), 201);
    }

    #[Route('/api/members/add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->request->all();

        $missingFields = $this->findEmptyRequiredFields($data);
        if ($missingFields) {
            return $this->json(['error' => 'Required fields are missing: ' . implode(', ', $missingFields)], 422);
        }

        $name = trim($data['name']);
        $birth = $data['birth_date'];

        try {
            $birthDate = new DateTime($birth);
        } catch (Throwable) {
            return $this->json(['error' => 'Invalid birth_date format, expected YYYY-MM-DD'], 422);
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email'], 422);
        }

        $phone = $this->formatPhoneNumber($data['phone']);
        if ($phone === null) {
            return $this->json(['error' => 'Invalid phone, expected +{digits}'], 422);
        }

        $member = new Member();
        $member
            ->setName($name)
            ->setBirthDate($birthDate)
            ->setEmail($data['email'] ?? null)
            ->setPhone($phone)
            ->setAddress($data['address'] ?? null)
            ->setComment($data['comment'] ?? null);

        $member->setChurchRole(Member::ROLE_CHURCH_MEMBER);

        if (!empty($data['pastor_id'])) {
            $pastor = $entityManager->getRepository(Member::class)->find($data['pastor_id']);
            if ($pastor) {
                $member->setPastor($pastor);
            }
        }

        if (!empty($data['diakon_id'])) {
            $diakon = $entityManager->getRepository(Member::class)->find($data['diakon_id']);
            if ($diakon) {
                $member->setDiakon($diakon);
            }
        }

        if (!empty($data['bapt_date'])) {
            try {
                $member->setBaptDate(new DateTime($data['bapt_date']));
            } catch (Throwable) {
                return $this->json(['error' => 'Invalid bapt_date format'], 422);
            }
        }

        $member->setActive((int) ($data['active'] ?? 1));
        $member->setLastUpdate(new DateTime());

        $entityManager->persist($member);
        $entityManager->flush();

        return $this->json(['id' => $member->getId()], 201);
    }

    #[Route('/api/members/edit/{id}', methods: ['POST', 'PUT'])]
    public function edit(Member $member, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->request->all();

        $emptyFields = $this->findEmptyRequiredFields($data, true);
        if ($emptyFields) {
            return $this->json(['error' => 'Fields cannot be empty: ' . implode(', ', $emptyFields)], 422);
        }

        if (isset($data['name'])) {
            $member->setName(trim($data['name']));
        }

        if (isset($data['birth_date'])) {
            try {
                $member->setBirthDate(new DateTime($data['birth_date']));
            } catch (Throwable) {
                return $this->json(['error' => 'Invalid birth_date format, expected YYYY-MM-DD'], 422);
            }
        }

        if (isset($data['email'])) {
            if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Invalid email'], 422);
            }
            $member->setEmail($data['email'] ?: null);
        }

        if (isset($data['phone'])) {
            $phone = $this->formatPhoneNumber($data['phone']);
            if ($phone === null) {
                return $this->json(['error' => 'Invalid phone, expected +{digits}'], 422);
            }
            $member->setPhone($phone);
        }

        if (isset($data['address'])) {
            $member->setAddress($data['address'] ?: null);
        }

        if (isset($data['comment'])) {
            $member->setComment($data['comment'] ?: null);
        }

        if (isset($data['role'])) {
            $validRoles = [Member::ROLE_CHURCH_MEMBER, Member::ROLE_DEACON, Member::ROLE_PRESBYTER];
            if (!in_array($data['role'], $validRoles)) {
                return $this->json(['error' => 'Invalid role'], 422);
            }
            $member->setChurchRole($data['role']);
        }

        if (isset($data['active'])) {
            $member->setActive((int) $data['active']);
        }

        if (isset($data['pastor_id'])) {
            $pastor = $data['pastor_id'] ? $entityManager->getRepository(Member::class)->find($data['pastor_id']) : null;
            $member->setPastor($pastor);
        }

        if (isset($data['diakon_id'])) {
            $diakon = $data['diakon_id'] ? $entityManager->getRepository(Member::class)->find($data['diakon_id']) : null;
            $member->setDiakon($diakon);
        }

        if (isset($data['bapt_date'])) {
            try {
                $member->setBaptDate($data['bapt_date'] ? new DateTime($data['bapt_date']) : null);
            } catch (Throwable) {
                return $this->json(['error' => 'Invalid bapt_date format'], 422);
            }
        }

        $member->setLastUpdate(new DateTime());

        $entityManager->flush();

        return $this->json(['status' => 'success']);
    }

    private function findEmptyRequiredFields(array $data, bool $onlyPresent = false): array
    {
        return array_values(array_filter(
            self::REQUIRED_FIELDS,
            fn(string $field) => (!$onlyPresent || array_key_exists($field, $data))
                && trim((string) ($data[$field] ?? '')) === ''
        ));
    }

    private function formatPhoneNumber(string $phone): ?string
    {
        $phone = preg_replace('/[\s\-().]/u', '', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        $phone = '+' . ltrim($phone, '+');

        return preg_match('/^\+\d{7,15}$/', $phone) ? $phone : null;
    }
}
