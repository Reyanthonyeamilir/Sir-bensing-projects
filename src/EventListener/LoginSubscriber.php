<?php
// src/EventListener/LoginSubscriber.php

namespace App\EventListener;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        if ($user instanceof User) {
            $activityLog = new ActivityLog();
            $activityLog->setUserId($user);
            $activityLog->setUsername($user->getUsername());
            
            // Set role based on user's roles
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles)) {
                $activityLog->setRole('ROLE_ADMIN');
            } elseif (in_array('ROLE_STAFF', $roles)) {
                $activityLog->setRole('ROLE_STAFF');
            } else {
                $activityLog->setRole('ROLE_USER');
            }
            
            $activityLog->setAction('LOGIN');
            $activityLog->setTargetData(json_encode(['description' => 'User logged in']));
            $activityLog->setEntityType('User');
            
            // Get IP address
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $activityLog->setIpAddress($request->getClientIp());
            }
            
            $this->entityManager->persist($activityLog);
            $this->entityManager->flush();
        }
    }
}