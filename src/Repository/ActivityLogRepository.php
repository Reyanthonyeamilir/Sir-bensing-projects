<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Get paginated activity logs with optional filters
     *
     * @param int $page Current page number (starting from 1)
     * @param int $limit Number of items per page
     * @param array $filters Array of filters (username, role, action, date)
     * @return Paginator
     */
    public function findPaginated(int $page, int $limit, array $filters = []): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');
        
        // Apply filters
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
        
        if (!empty($filters['date'])) {
            $queryBuilder->andWhere('DATE(a.createdAt) = :date')
                ->setParameter('date', $filters['date']);
        }
        
        // Pagination
        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        
        return new Paginator($queryBuilder);
    }

    /**
     * Find activity logs with filters
     *
     * @param array $filters Array of filters (username, role, action, date)
     * @return ActivityLog[]
     */
    public function findByFilters(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');
        
        // Apply filters
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
        
        if (!empty($filters['date'])) {
            $queryBuilder->andWhere('DATE(a.createdAt) = :date')
                ->setParameter('date', $filters['date']);
        }
        
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Count total logs (optionally with filters)
     *
     * @param array $filters Array of filters (username, role, action, date)
     * @return int
     */
    public function countLogs(array $filters = []): int
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');
        
        // Apply filters
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
        
        if (!empty($filters['date'])) {
            $queryBuilder->andWhere('DATE(a.createdAt) = :date')
                ->setParameter('date', $filters['date']);
        }
        
        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}