<?php

namespace App\Controller;

use App\Entity\Member;
use App\Repository\MembersRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/api/members')]
final class MembersController extends AbstractController
{
    #[Route(name: 'app_member_index', methods: ['GET'])]
    public function index(MembersRepository $memberRepository): JsonResponse
    {
        return $this->json([
            'members' => $memberRepository->findAll(),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Member $member): jsonResponse
    {
        return $this->json($member->toArray(), 201);
    }

    #[Route('/add', methods: ['POST'])]
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

        $member = new Member();
        $member->setName($name)->setBirthDate($birthDate);

        if (!empty($data['bapt_date'])) {
            try {
                $member->setBaptDate(new DateTime($data['bapt_date']));
            } catch (Throwable) {
                return $this->json(['error' => 'Invalid baptDate format'], 422);
            }
        }

        $member->setActive((int) ($data['active'] ?? 1));
        $member->setLastUpdate(new DateTime());

        $entityManager->persist($member);
        $entityManager->flush();

        return $this->json(['id' => $member->getId()], 201);
    }
}
