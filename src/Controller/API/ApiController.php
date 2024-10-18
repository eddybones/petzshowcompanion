<?php

namespace App\Controller\API;

use App\Repository\PetRepository;
use App\Service\API\AdminService;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ApiController extends AbstractController {
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function users(AdminService $service): JsonResponse {
        try {
            return new JsonResponse($service->getUsers(), Response::HTTP_OK);
        } catch(Exception $e) {}

        return new JsonResponse('Error getting users', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/api/petz/{userId}', name: 'api_petz_by_user', methods: ['GET'])]
    public function petzByUser(PetRepository $repo, int $userId): JsonResponse {
        try {
            $all = $repo->findBy(['user' => $userId], ['callName' => 'asc']);
            $petz = [];
            foreach($all as $pet) {
                $petz[] = [
                    'id'             => $pet->getId(),
                    'hash'           => $pet->getHash(),
                    'callName'       => $pet->getCallName(),
                    'showName'       => $pet->getShowName(),
                    'prefix'         => $pet->getPrefix(),
                    'hexerOrBreeded' => $pet->getHexerOrBreeder(),
                    'type'           => $pet->getType()->name,
                    'retired'        => $pet->getRetired(),
                    'sex'            => $pet->getSexName(),
                    'birthday'       => $pet->getBirthday() ? $pet->getBirthday()->format('Y-m-d') : '',
                    'addedOn'        => $pet->getAddedOn()->format('Y-m-d'),
                    'rollup'         => array_map(fn($item) => [
                        'showType' => $item->getShowType()->name,
                        'total'    => $item->getTotal(),
                    ], $pet->getPointsRollup()),
                    'tags'           => $pet->getTags()->map(fn($pet) => $pet->jsonSerialize())->toArray(),
                    'pic'            => $pet->getPic(),
                    'notes'          => $pet->getNotes(),
                ];
            }
            return new JsonResponse($petz, Response::HTTP_OK);
        } catch(Exception $e) {
        }

        return new JsonResponse('Error getting petz for user', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/api/upgrade/{version}', name: 'api_upgrade_site_version', methods: ['GET'])]
    public function upgradeSiteVersion(int $version): Response {
        $output = '';
        switch($version) {
            case 6:
                $output = $this->upgradeVersion6();
            break;
        }
        return new Response($output);
    }

    private function upgradeVersion6(): string {
        $output = '';

        $connection = $this->em->getConnection();

        try {
            $version = $connection->executeQuery('select scriptExecuted from schema_version where version = 6');
        } catch(Exception $e) {
            return 'Error determining script execution for version 6: ' . $e->getMessage();
        }

        if($version->fetchAllAssociative()[0]['scriptExecuted']) {
            return 'Version 6 upgrade script already executed.';
        }

        $output .= 'Adding hashes to users...<br>';
        try {
            $users = $connection->executeQuery('select id, email from user');
            foreach($users->fetchAllAssociative() as $user) {
                $hash = hash($_ENV['SMALL_HASH'], $user['email']);
                $added = $connection->executeStatement("update user set hash = '" . $hash . "' where id = " . $user['id']);
                if($added === 1) {
                    $output .= 'Added hash ' . $hash . ' for user ' . $user['id'] . '|' . $user['email'] . '<br>';
                } else {
                    $output .= 'Failed adding hash for user ' . $user['id'] . '|' . $user['email'] . '<br>';
                }
            }
        } catch(Exception $e) {
            $output .= 'Error adding hashes for users: ' . $e->getMessage() . '<br>';
        }

        $output .= '<br>Moving pet pics to hash folders...<br>';
        try {
            $sql = <<<SQL
                select pp.id, pp.file, u.hash
                from pet_pics pp
                inner join pet p on p.id = pp.pet_id
                inner join user u on u.id = p.user_id
                order by pp.id
                SQL;
            $pets = $connection->executeQuery($sql);
            foreach($pets->fetchAllAssociative() as $pet) {
                $path = $_ENV['PIC_PATH'] . DIRECTORY_SEPARATOR;
                $hashPath = $path . $pet['hash'] . DIRECTORY_SEPARATOR;
                if(!is_dir($hashPath)) {
                    $output .= 'Created folder ' . $hashPath . '<br>';
                    mkdir($hashPath);
                }
                $newFile = $hashPath . $pet['file'];
                $moved = rename($path . $pet['file'], $newFile);
                if($moved) {
                    $output .= 'Moved file for pet ' . $pet['id'] . ' to ' . $newFile . '<br>';
                    $updated = $connection->executeStatement("update pet_pics set file = ? where id = ?",
                        [
                            $pet['hash']  . DIRECTORY_SEPARATOR . $pet['file'],
                            $pet['id'],
                        ],
                        [
                            ParameterType::STRING,
                            ParameterType::INTEGER,
                        ]
                    );
                    if($updated === 1) {
                        $output .= 'Updated file reference for pet ' . $pet['id'] . '<br>';
                    } else {
                        $output .= 'Failed updating file reference for pet ' . $pet['id'] . '<br>';
                    }
                } else {
                    $output .= 'Error moving file for pet ' . $pet['id'] . '<br>';
                }
            }
        } catch(Exception $e) {
            $output .= 'Error moving pet pics to hash folders: ' . $e->getMessage() . '<br>';
        }

        try {
            $connection->executeStatement('update schema_version set scriptExecuted = 1 where version = 6');
        } catch(Exception $e) {
            $output .= 'Error finishing script execution: ' . $e->getMessage() . '<br>';
        }

        $output .= 'Done.';

        return $output;
    }
}