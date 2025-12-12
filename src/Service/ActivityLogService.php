<?php
// src/Service/ActivityLogService.php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack
    ) {}

    public function logActivity(
        string $action,
        string $description,
        ?User $user = null,
        array $data = []
    ): void {
        $user = $user ?? $this->getCurrentUser();

        $log = new ActivityLog();

        if ($user instanceof User) {
            $log->setUserId($user);
            $log->setUsername($user->getUserIdentifier());
            $log->setRole($this->resolveRole($user)); // This now returns lowercase
        } else {
            $log->setUsername('Anonymous');
            $log->setRole('ROLE_ANONYMOUS');
        }

        $log->setAction(strtoupper($action));

        $payload = ['description' => $description];
        if ($data) $payload['data'] = $data;

        $log->setTargetData(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $log->setEntityType($this->extractEntityType($description));

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function resolveRole(User $user): string
    {
        $roles = $user->getRoles();
        
        // Check each role and return the appropriate one
        foreach ($roles as $role) {
            $normalizedRole = strtolower($role); // Normalize to lowercase
            
            if ($normalizedRole === 'role_admin') {
                return 'ROLE_ADMIN';
            } elseif ($normalizedRole === 'role_staff') {
                return 'ROLE_staff'; // LOWERCASE 's'
            } elseif ($normalizedRole === 'role_moderator') {
                return 'ROLE_MODERATOR';
            }
        }
        
        // Default to ROLE_USER
        return 'ROLE_USER';
    }

    private function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) return null;
        $user = $token->getUser();
        return $user instanceof User ? $user : null;
    }

    private function extractEntityType(string $description): ?string
    {
        $lower = strtolower($description);
        if (str_contains($lower, 'user')) {
            return 'User';
        } elseif (str_contains($lower, 'product') || str_contains($lower, 'pet')) {
            return 'Petproducts';
        } elseif (str_contains($lower, 'order')) {
            return 'Order';
        }
        return null;
    }

    // Shortcut methods
    public function logCreate(string $entityType, $entityId, ?User $user = null, array $data = []): void
    {
        $this->logActivity('CREATE', "Created new $entityType", $user, ['entity_id' => $entityId, 'details' => $data]);
    }

    public function logUpdate(string $entityType, $entityId, ?User $user = null, array $changes = []): void
    {
        $this->logActivity('UPDATE', "Updated $entityType", $user, ['entity_id' => $entityId, 'changes' => $changes]);
    }

    public function logDelete(string $entityType, $entityId, ?User $user = null, string $description = ''): void
    {
        $desc = $description ?: "Deleted $entityType";
        $this->logActivity('DELETE', $desc, $user, ['entity_id' => $entityId]);
    }

    public function logLogin(?User $user = null): void
    {
        $username = $user ? $user->getUsername() : 'Unknown';
        $this->logActivity('LOGIN', "User logged in: $username", $user);
    }

    public function logView(string $entityType, $entityId, ?User $user = null): void
    {
        $this->logActivity('VIEW', "Viewed $entityType details", $user, ['entity_id' => $entityId]);
    }
}