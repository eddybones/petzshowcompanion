<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 *
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Tag::class);
    }

    public function save(Tag $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function saveCollection(ArrayCollection $collection, bool $flush = false): void {
        $manager = $this->getEntityManager();
        foreach($collection as $tag) {
            $manager->persist($tag);
        }
        if($flush) {
            $manager->flush();
        }
    }

    public function saveCollectionIgnoreDuplicates(ArrayCollection $collection, bool $flush = false): void {
        $manager = $this->getEntityManager();
        foreach($collection as $tag) {
            if(!$this->findOneBy(['user' => $tag->getUser(), 'name' => $tag->getName(), 'hash' => $tag->getHash()])) {
                $manager->persist($tag);
            }
        }
        if($flush) {
            $manager->flush();
        }
    }

    public function remove(Tag $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getByUserId(int $userId): array {
        $builder = $this->getEntityManager()->createQueryBuilder();
        return $builder->select('t')
            ->from(Tag::class, 't')
            ->where('t.user = ?1')
            ->orderBy('t.name', 'asc')
            ->setParameter(1, $userId)
            ->indexBy('t', 't.hash')
            ->getQuery()
            ->getResult();

    }
}