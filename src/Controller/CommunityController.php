<?php

namespace App\Controller;

use App\Repository\PetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\CommonMark\CommonMarkConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMAIL_VERIFIED')]
class CommunityController extends AbstractController {
    #[Route('/community', name: 'community', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response {
        $query = $em->createQuery(
            'SELECT u as User, p, size(u.petz) as PetCount FROM App\Entity\User u
            INNER JOIN u.profile p
            WHERE p.private = 0 AND p.username IS NOT NULL
            ORDER BY p.displayName ASC, p.username ASC'
        );
        /*
         * [
         *   User     => App\Entity\User,
         *   PetCount => int
         * ]
         */
        $users = $query->getResult();
        return $this->render('community/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/community/@{username}', name: 'userProfile', methods: ['GET'])]
    public function userProfile(EntityManagerInterface $em, PetRepository $repo, string $username): Response {
        $query = $em->createQuery(
            'SELECT u as User, p FROM App\Entity\User u
            INNER JOIN u.profile p
            WHERE p.private = 0 AND p.username = :username'
        )->setParameter('username', $username);
        $users = $query->getResult();
        $error = count($users) == 0;

        $user = ($error) ? null : $users[0]['User'];
        $petz = new ArrayCollection();
        if($user) {
            $petz = $repo->findBy(['user' => $user->getId()], ['callName' => 'asc']);
        }

        return $this->render('community/profile.html.twig', [
            'error' => $error,
            'user' => ($error) ? null : $users[0]['User'],
            'markdown' => new CommonMarkConverter([
                'html_input' => 'allow',
            ]),
            'petz' => $petz,
            'privacy' => $user->getPrivacy(),
        ]);
    }

    #[Route('/community/pet/{hash}', name: 'petProfile', methods: ['POST'])]
    public function profilePet(PetRepository $repo, string $hash): JsonResponse {
        try {
            $pet = $repo->findOneBy(['hash' => $hash]);
            return new JsonResponse(json_encode($pet), Response::HTTP_OK);
        } catch(Exception $e) {
            return new JsonResponse(['message' => 'Error getting pet data.'], Response::HTTP_BAD_REQUEST);
        }
    }
}