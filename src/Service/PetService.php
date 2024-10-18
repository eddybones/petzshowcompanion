<?php

namespace App\Service;

use App\Controller\PetException;
use App\Entity\Pet;
use App\Entity\Pic;
use App\Entity\Points;
use App\Entity\PointsRollup;
use App\Enum\PetType;
use App\Enum\ShowType;
use App\Repository\PetRepository;
use App\Repository\PointsRepository;
use App\Repository\PointsRollupRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class PetService {
    private readonly string $picPath;
    private EntityManagerInterface $em;
    private Filesystem $filesystem;
    private PetRepository $petRepo;
    private PointsRepository $pointsRepo;
    private PointsRollupRepository $rollupRepo;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        Filesystem             $filesystem,
        PetRepository          $petRepo,
        PointsRepository       $pointsRepo,
        PointsRollupRepository $rollupRepo,
        LoggerInterface        $logger
    ) {
        $this->picPath = $_ENV['PIC_PATH'] . DIRECTORY_SEPARATOR;
        $this->em = $em;
        $this->filesystem = $filesystem;
        $this->petRepo = $petRepo;
        $this->pointsRepo = $pointsRepo;
        $this->rollupRepo = $rollupRepo;
        $this->logger = $logger;
    }

    /**
     * This will get a new Pet from the request data or merge the request data with an existing Pet entity
     *
     * @param Request       $request
     * @param UserInterface $user
     * @param Pet|null      $existingPet
     * @param array         $allTags
     *
     * @return array [Pet, []]
     */
    public function getPetFromRequest(
        Request       $request,
        UserInterface $user,
        ?Pet          $existingPet = null,
        array         $allTags = []
    ): array {
        $callName = (string)$request->get('callname');
        $showName = (string)$request->get('showname');
        $prefix = (string)$request->get('prefix');
        $hexerOrBreeder = (string)$request->get('hexerOrBreeder');
        $year = $request->get('year');
        $month = $request->get('month');
        $day = $request->get('day');
        $type = (int)$request->get('type');
        $sex = $request->get('sex');
        $retired = (bool)$request->get('retired');
        $notes = (string)$request->get('notes');
        $private = (bool)$request->get('private');
        $tags = new ArrayCollection();

        foreach($request->request as $key => $value) {
            if(str_starts_with($key, 'tag-')) {
                $hash = explode('-', $key)[1];
                $tags->add($allTags[$hash]);
            }
        }

        if($sex === '') {
            $sex = null;
        }
        [$yearInt, $monthInt, $dayInt] = [(int)$year, (int)$month, (int)$day];
        if(
            $yearInt < 1 ||
            ($monthInt < 1 || $monthInt > 12) ||
            ($dayInt < 1 || $dayInt > 31)
        ) {
            $birthday = null;
        } else {
            $birthday = DateTimeImmutable::createFromFormat('Y-m-d', "$yearInt-$monthInt-$dayInt");
        }

        if($existingPet) {
            $pet = $existingPet
                ->setCallName($callName)
                ->setShowName($showName)
                ->setPrefix($prefix)
                ->setHexerOrBreeder($hexerOrBreeder)
                ->setBirthday($birthday)
                ->setType(PetType::from($type))
                ->setRetired($retired)
                ->setSex($sex)
                ->setNotes($notes)
                ->setModifiedOn(new DateTime())
                ->setTags($tags)
                ->setPrivate($private);
        } else {
            $pet = new Pet(
                $user,
                $showName,
                $callName,
                $notes,
                PetType::from($type),
                $retired,
                $sex,
                '',
                $prefix,
                $hexerOrBreeder,
                $birthday,
                $tags,
                null,
                $private
            );
        }

        return [
            $pet,
            [
                'year'  => $year ? $yearInt : '',
                'month' => $month ? $monthInt : '',
                'day'   => $day ? $dayInt : '',
            ],
        ];
    }

    public function getPetIfOwnedByUser(UserInterface $user, string $hash): Pet {
        $pet = $this->petRepo->findOneBy(['hash' => $hash, 'user' => $user]);

        if(!$pet) {
            throw new PetException('This pet does not exist or does not belong to you...');
        }

        return $pet;
    }

    public function picIsOwnedByUser(UserInterface $user, int $picId): bool {
        $connection = $this->em->getConnection();
        $sql = <<<SQL
            select u.id from pet_pics pp
            inner join pet p on p.id = pp.pet_id
            inner join user u on u.id = p.user_id
            where pp.id = :picId and u.id = :userId;
            SQL;
        $query = $connection->prepare($sql);
        $query->bindValue('picId', $picId, PDO::PARAM_INT);
        $query->bindValue('userId', $user->getId(), PDO::PARAM_INT);
        $data = $query->executeQuery();
        return (bool)count($data->fetchAllAssociative());
    }

    public function save(string $userHash, Pet $pet, ?array $pics, int $startSortOrderAt = 0): ?Pet {
        try {
            if($pics) {
                $collection = new ArrayCollection();
                // TODO: Get highest sort from existing pets
                $order = $startSortOrderAt;
                foreach($pics as $pic) {
                    if(++$order > 6) {
                        break;
                    }
                    $name = md5_file($pic->getRealPath()) . '.' . $pic->getClientOriginalExtension();
                    $userPicDir = $this->picPath . $userHash . DIRECTORY_SEPARATOR;
                    if(!is_dir($userPicDir)) {
                        mkdir($userPicDir);
                    }
                    $pic->move($userPicDir, $name);
                    $entity = new Pic($pet, $userHash . DIRECTORY_SEPARATOR . $name, $order);
                    $collection->add($entity);
                }
                $pet->setPics($collection);
            }
            $this->petRepo->save($pet, true);
            return $pet;
        } catch(Exception $e) {
            $this->logger->error('Could not save pet entity', [
                'exception' => $e,
            ]);
        }
        return null;
    }

    public function setHash(Pet $pet): bool {
        try {
            $pet->setHash(hash($_ENV['SMALL_HASH'], $pet->getId() . $pet->getCallName()));
            $this->petRepo->save($pet, true);
            return true;
        } catch(Exception $e) {
            $this->logger->error('Could not save pet entity', [
                'exception' => $e,
            ]);
        }
        return false;
    }

    public function delete(Pet $pet): bool {
        try {
            $pics = $pet->getPics();
            if(count($pics)) {
                foreach($pics as $pic) {
                    $this->filesystem->remove($this->picPath . $pic->getFile());
                }
            }
            $this->petRepo->remove($pet, true);
            return true;
        } catch(Exception $e) {
            $this->logger->error('Could not remove pet entity', [
                'exception' => $e,
            ]);
        }

        return false;
    }

    public function deletePic(int $picId): bool {
        $connection = $this->em->getConnection();
        $query = $connection->prepare('select pet_id, file from pet_pics where id = :picId');
        $query->bindValue('picId', $picId, PDO::PARAM_INT);
        $data = $query->executeQuery();
        $row = $data->fetchAllAssociative()[0];
        $file = $row['file'];
        $petId = $row['pet_id'];
        if(file_exists($this->picPath . $file)) {
            unlink($this->picPath . $file);
        }
        $query = $connection->prepare('delete from pet_pics where id = :picId');
        $query->bindValue('picId', $picId, PDO::PARAM_INT);
        $query->executeStatement();

        $reorder = <<<SQL
        with Pics as (
            select pp.id, row_number() over (order by `order`) as rownum from pet_pics pp
            inner join pet p on p.id = pp.pet_id
            where pet_id = :petId
        )
        update pet_pics pp inner join Pics p on pp.id = p.id set `order` = p.rownum;
        SQL;
        $query = $connection->prepare($reorder);
        $query->bindValue('petId', $petId, PDO::PARAM_INT);
        $query->executeStatement();

        return true;
    }

    public function addPoints(Pet $pet, ShowType $showType, int $points): bool {
        try {
            $entity = new Points($pet, $showType, $points);
            $this->pointsRepo->save($entity, true);
            $rollup = $this->rollupRepo->findOneBy([
                'pet'      => $pet,
                'showType' => $showType->value,
            ]);
            if(!$rollup) {
                $rollup = new PointsRollup($pet, $showType);
            }
            $rollup->incrementTotal($points);
            $this->rollupRepo->save($rollup, true);
            return true;
        } catch(Exception $e) {
            $this->logger->error('Could not add show points', [
                'exception' => $e,
            ]);
        }
        return false;
    }

    public function modifyPoints(Pet $pet, string $hash, array $points): array {
        $connection = $this->em->getConnection();

        try {
            $connection->beginTransaction();

            foreach($points as $point) {
                if($point['delete'] ?? false) {
                    $query = $connection->prepare('delete from points where id = :id and pet_id in (select id from pet where hash = :hash)');
                    $query->bindValue('id', $point['id'], PDO::PARAM_INT);
                    $query->bindValue('hash', $hash, PDO::PARAM_STR);
                    $query->executeStatement();
                    continue;
                }
                $query = $connection->prepare('update points set show_type = :showtype, points = :points where id = :id and pet_id in (select id from pet where hash = :hash)');
                $query->bindValue('showtype', $point['showType'], PDO::PARAM_INT);
                $query->bindValue('points', $point['points'], PDO::PARAM_INT);
                $query->bindValue('id', $point['id'], PDO::PARAM_INT);
                $query->bindValue('hash', $hash, PDO::PARAM_STR);
                $query->executeStatement();
            }

            $sql = <<<SQL
            select sum(points) as total, show_type as showType from points
            left join pet on pet.id = points.pet_id
            where pet.hash = :hash
            group by show_type
            order by show_type;
            SQL;
            $query = $connection->prepare($sql);
            $query->bindValue('hash', $hash, PDO::PARAM_STR);
            $data = $query->executeQuery();
            $rollup = $data->fetchAllAssociative();
            foreach($rollup as $row) {
                $query = $connection->prepare('insert into points_rollup (pet_id, show_type, total) values (:petid, :showtype, :total) on duplicate key update total=:total');
                $query->bindValue('petid', $pet->getId(), PDO::PARAM_INT);
                $query->bindValue('showtype', $row['showType'], PDO::PARAM_INT);
                $query->bindValue('total', $row['total'], PDO::PARAM_INT);
                $query->executeStatement();
            }

            // Remove rollup records that no longer apply
            $showtypes = array_map(fn($item) => $item['showType'], $rollup);
            if(!count($showtypes)) {
                $query = $connection->prepare('delete from points_rollup where pet_id = :petid');
            } else {
                $query = $connection->prepare('delete from points_rollup where show_type not in (' . implode(',', $showtypes) . ') and pet_id = :petid');
            }
            $query->bindValue('petid', $pet->getId(), PDO::PARAM_INT);
            $query->executeStatement();
            
            $connection->commit();

            return $rollup;
        } catch(Exception $e) {
            $connection->rollBack();
            $this->logger->error('Could not modify points', [
                'exception' => $e,
            ]);
            throw new Exception('Error modifying points');
        }
    }

    public function picsOwnedByUser(?UserInterface $user, array $picOrder): bool {
        $connection = $this->em->getConnection();

        try {
            $sql = <<<SQL
            select count(*) as picCount from pet_pics pp
            inner join pet p on p.id = pp.pet_id
            inner join user u on u.id = p.user_id
            where u.id = ? and pp.id in (?)
            SQL;
            $data = $connection->executeQuery($sql, [$user->getId(), $picOrder], [PDO::PARAM_INT, ArrayParameterType::INTEGER]);
            return count($picOrder) === $data->fetchAllAssociative()[0]['picCount'];
        } catch(Exception $e) {
            $this->logger->error('Could not get pics for user.', [
                'exception' => $e,
            ]);
            throw new Exception('Error getting pics for user.');
        }
    }

    public function resortPics(array $order): void {
        $connection = $this->em->getConnection();

        try {
            $picCount = count($order);
            for($i = 0; $i < $picCount; ++$i) {
                $query = $connection->prepare('update pet_pics set `order` = :order where id = :picId');
                $query->bindValue('order', $i + 1, PDO::PARAM_INT);
                $query->bindValue('picId', $order[$i], PDO::PARAM_INT);
                $query->executeStatement();
            }
        } catch(Exception $e) {
            $this->logger->error('Could not resort pics.', [
                'exception' => $e,
            ]);
            throw new Exception('Error resorting pics.');
        }
    }
}