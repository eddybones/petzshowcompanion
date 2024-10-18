<?php

namespace App\Repository;

use App\Entity\Pic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pic>
 *
 * @method Pic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pic|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pic[]    findAll()
 * @method Pic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PicRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Pic::class);
    }

    public function save(Pic $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Pic $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }
}