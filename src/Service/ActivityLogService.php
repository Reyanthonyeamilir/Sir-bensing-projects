<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivityLogService
{
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Log a user activity
     */
    public function logActivity(
        string $action,
        string $targetData,
        ?User $user = null,
        ?string $username = null,
        ?string $role = null
    ): ActivityLog {
        // Get current user from token storage if not provided
        if (!$user) {
            $token = $this->tokenStorage->getToken();
            if ($token && $token->getUser() instanceof User) {
                $user = $token->getUser();
            }
        }

        // Create activity log entity
        $activityLog = new ActivityLog();
        
        if ($user instanceof User) {
            $activityLog->setUserId($user);
            
            // Auto-fill user details if not provided
            if (!$username) {
                $activityLog->setUsername($user->getUserIdentifier());
            }
            
            if (!$role) {
                // Get the highest role (excluding ROLE_USER if there are others)
                $roles = $user->getRoles();
                if (count($roles) > 1) {
                    // Remove ROLE_USER from the list to get higher roles
                    $filteredRoles = array_filter($roles, fn($r) => $r !== 'ROLE_USER');
                    $activityLog->setRole(!empty($filteredRoles) ? reset($filteredRoles) : 'ROLE_USER');
                } else {
                    $activityLog->setRole($roles[0] ?? 'ROLE_USER');
                }
            }
        } else {
            $activityLog->setUsername($username ?? 'System');
            $activityLog->setRole($role ?? 'ROLE_ANONYMOUS');
        }
        
        $activityLog->setAction($action);
        $activityLog->setTargetData($targetData);
        $activityLog->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
        
        return $activityLog;
    }

    /**
     * Log CRUD operations
     */
    public function logCreate(string $entityType, mixed $entityId, ?User $user = null, array $additionalData = []): ActivityLog
    {
        $targetData = sprintf('%s with ID: %s', $entityType, $entityId);
        if (!empty($additionalData)) {
            $targetData .= sprintf(' - Data: %s', json_encode($additionalData));
        }
        
        return $this->logActivity('CREATE', $targetData, $user);
    }

    public function logUpdate(string $entityType, mixed $entityId, ?User $user = null, array $changes = []): ActivityLog
    {
        $targetData = sprintf('%s with ID: %s', $entityType, $entityId);
        if (!empty($changes)) {
            $targetData .= sprintf(' - Changes: %s', json_encode($changes));
        }
        
        return $this->logActivity('UPDATE', $targetData, $user);
    }

    public function logDelete(string $entityType, mixed $entityId, ?User $user = null, string $reason = ''): ActivityLog
    {
        $targetData = sprintf('%s with ID: %s', $entityType, $entityId);
        if ($reason) {
            $targetData .= sprintf(' - Reason: %s', $reason);
        }
        
        return $this->logActivity('DELETE', $targetData, $user);
    }

    /**
     * Log authentication events
     */
    public function logLogin(User $user): ActivityLog
    {
        return $this->logActivity('LOGIN', 'User logged in successfully', $user);
    }

    public function logLogout(?User $user): ActivityLog
    {
        return $this->logActivity('LOGOUT', 'User logged out', $user);
    }

    public function logFailedLogin(string $username, string $ip = ''): ActivityLog
    {
        $targetData = 'Failed login attempt';
        if ($ip) {
            $targetData .= sprintf(' from IP: %s', $ip);
        }
        
        return $this->logActivity(
            'FAILED_LOGIN',
            $targetData,
            null,
            $username,
            'ROLE_ANONYMOUS'
        );
    }

    public function logRegistration(User $user): ActivityLog
    {
        return $this->logActivity(
            'REGISTER',
            'New user registered',
            $user
        );
    }

    /**
     * Log user management events
     */
    public function logPasswordChange(User $user): ActivityLog
    {
        return $this->logActivity('PASSWORD_CHANGE', 'Password changed', $user);
    }

    public function logProfileUpdate(User $user, array $changedFields): ActivityLog
    {
        $targetData = sprintf('Updated profile fields: %s', implode(', ', array_keys($changedFields)));
        
        return $this->logActivity('PROFILE_UPDATE', $targetData, $user);
    }

    public function logRoleChange(User $user, array $oldRoles, array $newRoles, ?User $admin = null): ActivityLog
    {
        $targetData = sprintf('Roles changed from [%s] to [%s]', 
            implode(', ', $oldRoles), 
            implode(', ', $newRoles)
        );
        
        return $this->logActivity('ROLE_CHANGE', $targetData, $admin ?? $user);
    }

    /**
     * Log system events
     */
    public function logSystemEvent(string $event, string $description, array $data = []): ActivityLog
    {
        $targetData = $description;
        if (!empty($data)) {
            $targetData .= sprintf(' - Data: %s', json_encode($data));
        }
        
        return $this->logActivity(
            'SYSTEM_EVENT',
            $targetData,
            null,
            'System',
            'ROLE_SYSTEM'
        );
    }

    /**
     * Log error events
     */
    public function logError(string $error, ?string $location = null): ActivityLog
    {
        $targetData = $error;
        if ($location) {
            $targetData .= sprintf(' at %s', $location);
        }
        
        return $this->logActivity(
            'ERROR',
            $targetData,
            null,
            'System',
            'ROLE_SYSTEM'
        );
    }

    /**
     * Query methods
     */
    public function getUserActivities(User $user, int $limit = 50): array
    {
        return $this->entityManager
            ->getRepository(ActivityLog::class)
            ->findBy(
                ['user_id' => $user],
                ['created_at' => 'DESC'],
                $limit
            );
    }

    public function getActivitiesByAction(string $action, int $limit = 50): array
    {
        return $this->entityManager
            ->getRepository(ActivityLog::class)
            ->findBy(
                ['Action' => $action],
                ['created_at' => 'DESC'],
                $limit
            );
    }

    public function getRecentActivities(int $limit = 100, ?\DateTimeInterface $since = null): array
    {
        $qb = $this->entityManager
            ->getRepository(ActivityLog::class)
            ->createQueryBuilder('a')
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit);
            
        if ($since) {
            $qb->where('a.created_at >= :since')
                ->setParameter('since', $since);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Search activities with multiple criteria
     */
    public function searchActivities(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->entityManager
            ->getRepository(ActivityLog::class)
            ->createQueryBuilder('a');
        
        if (isset($criteria['user']) && $criteria['user'] instanceof User) {
            $qb->andWhere('a.user_id = :user')
                ->setParameter('user', $criteria['user']);
        }
        
        if (isset($criteria['username'])) {
            $qb->andWhere('a.username LIKE :username')
                ->setParameter('username', '%' . $criteria['username'] . '%');
        }
        
        if (isset($criteria['role'])) {
            $qb->andWhere('a.Role = :role')
                ->setParameter('role', $criteria['role']);
        }
        
        if (isset($criteria['action'])) {
            $qb->andWhere('a.Action = :action')
                ->setParameter('action', $criteria['action']);
        }
        
        if (isset($criteria['search'])) {
            $qb->andWhere('a.Target_Data LIKE :search OR a.username LIKE :search')
                ->setParameter('search', '%' . $criteria['search'] . '%');
        }
        
        if (isset($criteria['startDate'])) {
            $qb->andWhere('a.created_at >= :startDate')
                ->setParameter('startDate', $criteria['startDate']);
        }
        
        if (isset($criteria['endDate'])) {
            $qb->andWhere('a.created_at <= :endDate')
                ->setParameter('endDate', $criteria['endDate']);
        }
        
        $qb->orderBy('a.created_at', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select([
                'COUNT(a.id) as total_activities',
                'COUNT(DISTINCT a.username) as unique_users',
                'a.Action',
                'DATE(a.created_at) as date'
            ])
            ->from(ActivityLog::class, 'a')
            ->where('a.created_at BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('a.Action, DATE(a.created_at)')
            ->orderBy('date', 'DESC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Clean old logs (for maintenance)
     */
    public function cleanOldLogs(\DateTimeInterface $olderThan): int
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->delete(ActivityLog::class, 'a')
            ->where('a.created_at < :date')
            ->setParameter('date', $olderThan);
        
        return $qb->getQuery()->execute();
    }
}