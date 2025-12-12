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

    public function findPaginated(int $page, int $limit, array $filters = []): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('al')
            ->orderBy('al.createdAt', 'DESC');

        // Apply filters
        if (!empty($filters['username'])) {
            $queryBuilder->andWhere('al.username LIKE :username')
                ->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['role'])) {
            // Handle both uppercase and lowercase for staff
            if ($filters['role'] === 'ROLE_staff') {
                $queryBuilder->andWhere('al.role = :role')
                    ->setParameter('role', 'ROLE_staff'); // lowercase
            } else {
                $queryBuilder->andWhere('al.role = :role')
                    ->setParameter('role', $filters['role']);
            }
        }

        if (!empty($filters['action'])) {
            $queryBuilder->andWhere('al.action = :action')
                ->setParameter('action', $filters['action']);
        }

        if (!empty($filters['entityType'])) {
            $queryBuilder->andWhere('al.entityType = :entityType')
                ->setParameter('entityType', $filters['entityType']);
        }

        if (!empty($filters['date'])) {
            $startDate = new \DateTime($filters['date'] . ' 00:00:00');
            $endDate = new \DateTime($filters['date'] . ' 23:59:59');
            $queryBuilder->andWhere('al.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate);
        }

        $query = $queryBuilder->getQuery();

        // Pagination
        $query->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query, true);
    }

    public function findByFilters(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('al')
            ->orderBy('al.createdAt', 'DESC');

        // Apply filters
        if (!empty($filters['username'])) {
            $queryBuilder->andWhere('al.username LIKE :username')
                ->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['role'])) {
            // Handle both uppercase and lowercase for staff
            if ($filters['role'] === 'ROLE_staff') {
                $queryBuilder->andWhere('al.role = :role')
                    ->setParameter('role', 'ROLE_staff'); // lowercase
            } else {
                $queryBuilder->andWhere('al.role = :role')
                    ->setParameter('role', $filters['role']);
            }
        }

        if (!empty($filters['action'])) {
            $queryBuilder->andWhere('al.action = :action')
                ->setParameter('action', $filters['action']);
        }

        if (!empty($filters['entityType'])) {
            $queryBuilder->andWhere('al.entityType = :entityType')
                ->setParameter('entityType', $filters['entityType']);
        }

        if (!empty($filters['date'])) {
            $startDate = new \DateTime($filters['date'] . ' 00:00:00');
            $endDate = new \DateTime($filters['date'] . ' 23:59:59');
            $queryBuilder->andWhere('al.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}