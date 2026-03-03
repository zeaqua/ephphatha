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

        $name = trim($data['name'] ?? '');
        $birth = $data['birth_date'] ?? '';
        if ($name === '' || $birth === '') {
            return $this->json(['error' => 'name and birth_date are required (YYYY-MM-DD)'], 422);
        }

        try {
            $birthDate = new DateTime($birth);
        } catch (Throwable) {
            return $this->json(['error' => 'Invalid birth_date format, expected YYYY-MM-DD'], 422);
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email'], 422);
        }

        $member = new Member();
        $member
            ->setName($name)
            ->setBirthDate($birthDate)
            ->setEmail($data['email'] ?? null)
            ->setPhone($data['phone'] ?? null)
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

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                return $this->json(['error' => 'name cannot be empty'], 422);
            }
            $member->setName($name);
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
            $member->setPhone($data['phone'] ?: null);
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
}
