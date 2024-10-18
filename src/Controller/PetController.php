<?php

namespace App\Controller;

use App\Entity\Pet;
use App\Enum\PetType;
use App\Enum\PoseRankPoints;
use App\Enum\RankPoints;
use App\Enum\Sex;
use App\Enum\ShowType;
use App\Repository\PetRepository;
use App\Repository\PointsRollupRepository;
use App\Repository\TagRepository;
use App\Service\PetService;
use App\Service\ShowTitleService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('ROLE_EMAIL_VERIFIED')]
class PetController extends AbstractController {
    private const DS = DIRECTORY_SEPARATOR;

    private PetService $petService;
    private TagRepository $tagRepo;
    private LoggerInterface $logger;

    public function __construct(
        PetService      $petService,
        TagRepository   $tagRepo,
        Filesystem      $filesystem,
        string          $projectDir,
        LoggerInterface $logger,
    ) {
        $this->petService = $petService;
        $this->tagRepo = $tagRepo;
        $this->logger = $logger;

        $path = $_ENV['PIC_PATH'];
        if(!$filesystem->exists($path)) {
            $filesystem->mkdir($path);
            $filesystem->symlink($path, $projectDir . self::DS . 'public' . self::DS . 'pics');
            $filesystem->chown($path, 'www-data', true);
            $filesystem->chgrp($path, 'www-data', true);
        }
    }

    #[Route('/petz/list', name: 'petz_list', methods: ['GET'])]
    public function list(PetRepository $repo): Response {
        $user = $this->getUser();
        $petz = $repo->findBy(['user' => $user->getId()], ['callName' => 'asc']);
        return $this->render('petz/list.html.twig', [
            'petz'           => $petz,
            'showTypes'      => ShowType::cases(),
            'rankPoints'     => RankPoints::cases(),
            'poseRankPoints' => PoseRankPoints::cases(),
            'sexTypes'       => Sex::cases(),
            'allTags'        => $this->tagRepo->getByUserId($this->getUser()->getId()),
            'species'        => PetType::cases(),
            'useCompactView' => $user->getOptions()->useCompactView(),
        ]);
    }

    #[Route('/petz/add', name: 'petz_add', methods: ['GET'])]
    public function addPet(): Response {
        $user = $this->getUser();
        return $this->render('petz/pet.html.twig', [
            'add'            => true,
            'error'          => null,
            'pet'            => new Pet($this->getUser()),
            'petTypes'       => PetType::cases(),
            'sexTypes'       => Sex::cases(),
            'allTags'        => $this->tagRepo->getByUserId($user->getId()),
            'species'        => PetType::cases(),
            'useCompactView' => $user->getOptions()->useCompactView(),
        ]);
    }

    #[Route('/petz/add', name: 'petz_add_action', methods: ['POST'])]
    public function addPetAction(Request $request): Response {
        $pics = $request->files->get('pics');
        $user = $this->getUser();
        $allTags = $this->tagRepo->getByUserId($user->getId());
        [$pet, $birthdayParts] = $this->petService->getPetFromRequest(
            $request,
            $user,
            null,
            $allTags
        );

        if(!strlen($pet->getCallName())) {
            return $this->render('petz/pet.html.twig', [
                'add'            => true,
                'error'          => true,
                'pet'            => $pet,
                'birthdayParts'  => $birthdayParts,
                'petTypes'       => PetType::cases(),
                'sexTypes'       => Sex::cases(),
                'allTags'        => $allTags,
                'useCompactView' => $user->getOptions()->useCompactView(),
            ]);
        }

        $pet = $this->petService->save($user->getHash(), $pet, $pics);
        if($pet && $this->petService->setHash($pet)) {
            // TODO: Return to list, anchored to newly added pet, highlighted temporarily with CSS styling that fades out
            return $this->redirectToRoute('petz_list');
        }

        // TODO: Otherwise show error page...
    }

    #[Route('/petz/{hash}/delete/confirm', name: 'petz_delete_confirm', methods: ['GET'])]
    public function deletePetConfirm(string $hash): Response {
        $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);

        return $this->render('petz/confirm_delete.html.twig', [
            'error' => null,
            'pet'   => $pet,
        ]);
    }

    #[Route('/petz/{hash}/delete/confirm', name: 'petz_delete_action', methods: ['POST'])]
    public function deletePetAction(string $hash, Request $request): Response {
        $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);
        $confirmed = (bool)$request->get('confirm');
        if(!$confirmed) {
            return $this->render('petz/confirm_delete.html.twig', [
                'error' => true,
                'pet'   => $pet,
            ]);
        }

        if($this->petService->delete($pet)) {
            // TODO: Add message so user knows what happened...
            return $this->redirectToRoute('petz_list');
        }

        // TODO: Otherwise show error page...
    }

    /**
     * @throws PetException
     */
    #[Route('/petz/{hash}/edit', name: 'petz_edit', methods: ['GET'])]
    public function editPet(string $hash): Response {
        $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);

        return $this->render('petz/pet.html.twig', [
            'add'      => false,
            'error'    => null,
            'pet'      => $pet,
            'petTypes' => PetType::cases(),
            'sexTypes' => Sex::cases(),
            'allTags'  => $this->tagRepo->getByUserId($this->getUser()->getId()),
        ]);
    }

    #[Route('/petz/{hash}/edit', name: 'petz_edit_action', methods: ['POST'])]
    public function editPetAction(string $hash, Request $request): Response {
        $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);
        $pics = $request->files->get('pics');
        $allTags = $this->tagRepo->getByUserId($this->getUser()->getId());
        [$updatedPet, $birthdayParts] = $this->petService->getPetFromRequest(
            $request,
            $this->getUser(),
            $pet,
            $allTags
        );

        if(!strlen($updatedPet->getCallName())) {
            return $this->render('petz/pet.html.twig', [
                'add'           => false,
                'error'         => true,
                'pet'           => $updatedPet,
                'birthdayParts' => $birthdayParts,
                'petTypes'      => PetType::cases(),
                'sexTypes'      => Sex::cases(),
                'allTags'       => $allTags,
            ]);
        }

        $startSortOrderAt = count($pet->getPics());
        if($this->petService->save($this->getUser()->getHash(), $updatedPet, $pics, $startSortOrderAt)) {
            // TODO: Return to list, anchored to newly added pet, highlighted temporarily with CSS styling that fades out
            return $this->redirectToRoute('petz_list');
        }

        // TODO: Otherwise show error page...
    }

    #[Route('/pet/{hash}/pics/add', name: 'petz_pics_add', methods: ['POST'])]
    public function addPicsAction(string $hash, Request $request): JsonResponse {
        $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);
        $pics = $request->files->all();
        $startSortOrderAt = count($pet->getPics());
        if($this->petService->save($this->getUser()->getHash(), $pet, $pics, $startSortOrderAt)) {
            return new JsonResponse();
        }

        // TODO: Otherwise show error page...
    }

    #[Route('/petz/{hash}/points/add', name: 'petz_points_add_action', methods: ['POST'])]
    public function addPointsAction(string $hash, Request $request, PointsRollupRepository $rollupRepo): JsonResponse {
        try {
            $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);
            $showType = ShowType::from((int)$request->get('showtype'));
            $rank = (int)$request->get('rank');
            if($showType == ShowType::Pose) {
                $points = PoseRankPoints::from($rank)->value;
            } else {
                $points = RankPoints::from($rank)->value;
            }

            if($this->petService->addPoints($pet, $showType, $points)) {
                // TODO: This rollup stuff should go in its own function and it can be tested independently.
                $rollup = [];
                foreach($rollupRepo->findBy(['pet' => $pet], ['total' => 'desc']) as $type) {
                    $rollup[$type->getShowType()->name] = [
                        'type'  => $type->getShowType()->name,
                        'total' => $type->getTotal(),
                        'title' => ShowTitleService::getTitle($type->getShowType(), $pet->getType(), $type->getTotal()),
                    ];
                }
                return new JsonResponse([
                    'message' => "Added {$points} point" . ($points > 1 ? 's.' : '.'),
                    'rollup'  => array_values($rollup),
                ], Response::HTTP_OK);
            }
        } catch(Exception $e) {
        }

        return new JsonResponse([
            'message' => 'There was an error adding points for your pet.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/petz/{hash}/points', name: 'petz_points_list', methods: ['GET'])]
    public function getPoints(string $hash): JsonResponse {
        try {
            $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);
            $points = [];
            foreach($pet->getPoints() as $point) {
                $points[] = [
                    'id'       => $point->getId(),
                    'showType' => $point->getShowType()->value,
                    'points'   => $point->getPoints(),
                    'addedOn'  => $point->getAddedOn()->format('Y-m-d'),
                ];
            }
            return new JsonResponse($points, Response::HTTP_OK);
        } catch(Exception $e) {
        }

        return new JsonResponse([
            'message' => 'There was an error getting points for your pet.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/petz/{hash}/points', name: 'petz_points_update', methods: ['POST'])]
    public function updatePoints(string $hash, Request $request): JsonResponse {
        $points = json_decode($request->getContent(), true);
        try {
            $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);
            $rollup = $this->petService->modifyPoints($pet, $hash, $points);
            // This loop probably could have gone in the service function, but whatever.
            foreach($rollup as &$point) {
                $showType = ShowType::from($point['showType']);
                $point['type'] = $showType->name;
                $point['title'] = ShowTitleService::getTitle($showType, $pet->getType(), $point['total']);
            }
        } catch(Exception $e) {
            $this->logger->error('Error modifying pet points', ['exception' => $e]);
            return new JsonResponse([
                'message' => 'There was an error modifying points for your pet.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(json_encode($rollup), Response::HTTP_OK);
    }

    #[Route('/petz/{hash}/pics', name: 'petz_pics', methods: ['GET'])]
    public function listPics(string $hash, SerializerInterface $serializer): JsonResponse {
        try {
            $pet = $this->petService->getPetIfOwnedByUser($this->getUser(), $hash);
        } catch(Exception $e) {
            $this->logger->error('Error getting pet pics', ['exception' => $e]);
            return new JsonResponse([
                'message' => 'There was an error getting pics for your pet.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $pics = [];
        foreach($pet->getPics() as $pic) {
            $pics[] = [
                'id' => $pic->getId(),
                'file' => $pic->getFile(),
                'order' => $pic->getOrder(),
            ];
        }

        return new JsonResponse(json_encode($pics), Response::HTTP_OK);
    }

    #[Route('/pics/{picId}/delete', name: 'pic_delete', methods: ['DELETE'])]
    public function deletePic(int $picId): JsonResponse {
        try {
            if(!$this->petService->picIsOwnedByUser($this->getUser(), $picId)) {
                throw new Exception('Pic ID' . $picId . 'is not owned by user ' . $this->getUser()->getUserIdentifier());
            }
            $this->petService->deletePic($picId);
        } catch(Exception $e) {
            $this->logger->error('Error deleting pic', ['exception' => $e]);
            return new JsonResponse([
                'message' => 'There was an error deleting this pic of your pet.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse('Pic deleted.', Response::HTTP_OK);
    }

    #[Route('/pics/resort', name: 'pics_resort', methods: ['POST'])]
    public function resortPics(Request $request): JsonResponse {
        try {
            $order = json_decode($request->getContent());
            if(!$this->petService->picsOwnedByUser($this->getUser(), $order)) {
                throw new Exception('Pics not owned by user ' . $this->getUser()->getUserIdentifier());
            }
            $this->petService->resortPics($order);
        } catch(Exception $e) {
            $this->logger->error('Error resorting pics', ['exception' => $e]);
            return new JsonResponse([
                'message' => 'There was an error saving your pic sort order.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse('Pic resorted.', Response::HTTP_OK);
    }
}