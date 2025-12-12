<?php
// src/Service/ActivityLogger.php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security; // Symfony 6.2+

class ActivityLogger
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    // ... rest of your methods remain exactly the same as above ...
}