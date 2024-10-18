<?php

namespace App\Repository;

use App\Entity\PointsRollup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PointsRollup>
 *
 * @method PointsRollup|null find($id, $lockMode = null, $lockVersion = null)
 * @method PointsRollup|null findOneBy(array $criteria, array $orderBy = null)
 * @method PointsRollup[]    findAll()
 * @method PointsRollup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PointsRollupRepository extends ServiceEntityRepository{
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, PointsRollup::class);
    }

    public function save(PointsRollup $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PointsRollup $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }
}