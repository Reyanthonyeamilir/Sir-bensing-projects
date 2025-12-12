<?php
// src/Repository/ActivityLogRepository.php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function findPaginated(int $page = 1, int $limit = 20, array $filters = []): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        // Apply filters WITHOUT entity_type for now
        if (!empty($filters['username'])) {
            $queryBuilder->andWhere('a.username LIKE :username')
                ->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['role'])) {
            $queryBuilder->andWhere('a.role = :role')
                ->setParameter('role', $filters['role']);
        }

        if (!empty($filters['action'])) {
            $queryBuilder->andWhere('a.action = :action')
                ->setParameter('action', $filters['action']);
        }

        // Temporarily comment out entityType filter until column exists
        // if (!empty($filters['entityType'])) {
        //     $queryBuilder->andWhere('a.entityType = :entityType')
        //         ->setParameter('entityType', $filters['entityType']);
        // }

        if (!empty($filters['date'])) {
            $date = new \DateTime($filters['date'], new \DateTimeZone('Asia/Manila'));
            $nextDay = clone $date;
            $nextDay->modify('+1 day');
            
            $queryBuilder->andWhere('a.createdAt >= :startDate')
                ->andWhere('a.createdAt < :endDate')
                ->setParameter('startDate', $date)
                ->setParameter('endDate', $nextDay);
        }

        $query = $queryBuilder->getQuery();
        
        // Pagination
        $firstResult = ($page - 1) * $limit;
        $query->setFirstResult($firstResult)
              ->setMaxResults($limit);

        return new Paginator($query);
    }

    public function findByFilters(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        // Same filters without entityType
        if (!empty($filters['username'])) {
            $queryBuilder->andWhere('a.username LIKE :username')
                ->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['role'])) {
            $queryBuilder->andWhere('a.role = :role')
                ->setParameter('role', $filters['role']);
        }

        if (!empty($filters['action'])) {
            $queryBuilder->andWhere('a.action = :action')
                ->setParameter('action', $filters['action']);
        }

        // Temporarily comment out
        // if (!empty($filters['entityType'])) {
        //     $queryBuilder->andWhere('a.entityType = :entityType')
        //         ->setParameter('entityType', $filters['entityType']);
        // }

        if (!empty($filters['date'])) {
            $date = new \DateTime($filters['date'], new \DateTimeZone('Asia/Manila'));
            $nextDay = clone $date;
            $nextDay->modify('+1 day');
            
            $queryBuilder->andWhere('a.createdAt >= :startDate')
                ->andWhere('a.createdAt < :endDate')
                ->setParameter('startDate', $date)
                ->setParameter('endDate', $nextDay);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}