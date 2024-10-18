<?php

namespace App\Service\API;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class AdminService {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface        $logger
    ) {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function getUsers(): array {
        try {
            $connection = $this->em->getConnection();
            $sql = <<<SQL
                select u.id, u.email, date_format(u.dateAdded, '%Y-%m-%d') as dateAdded, count(p.id) as totalPetz
                from user u
                left join pet p on u.id = p.user_id
                group by u.id, u.email
                SQL;

            $query = $connection->prepare($sql);
            $result = $query->executeQuery();
            return $result->fetchAllAssociative();
        } catch(Throwable $e) {
            $this->logger->error('Error getting users.', [
                'exception' => $e,
            ]);
        }

        return [];
    }
}