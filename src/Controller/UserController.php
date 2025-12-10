<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, ActivityLogService $activityLogService): Response
    {
        $activityLogService->logActivity('VIEW_LIST', 'Viewed user list', $this->getUser());
        
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogService $activityLogService
    ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password for new user
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } else {
                // Set a default password
                $hashedPassword = $passwordHasher->hashPassword($user, 'changeme123');
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $activityLogService->logCreate('User', $user->getId(), $this->getUser(), [
                'username' => $user->getUsername(),
                'roles' => $user->getRoles()
            ]);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        $activityLogService->logActivity('VIEW_FORM', 'Viewed new user form', $this->getUser());

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/profile', name: 'app_user_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogService $activityLogService
    ): Response {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $oldUsername = $user->getUsername();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $changes = [];
            
            // Check if username changed
            if ($oldUsername !== $user->getUsername()) {
                $changes['username'] = [
                    'old' => $oldUsername,
                    'new' => $user->getUsername()
                ];
                
                // Check if username is already taken
                $existingUser = $entityManager->getRepository(User::class)
                    ->findOneBy(['username' => $user->getUsername()]);
                    
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Username already taken!');
                    return $this->redirectToRoute('app_user_profile');
                }
            }

            // Handle password change
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($newPassword) {
                // Verify current password
                if (!$currentPassword) {
                    $this->addFlash('error', 'Current password is required to change password');
                    return $this->redirectToRoute('app_user_profile');
                }

                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Current password is incorrect');
                    return $this->redirectToRoute('app_user_profile');
                }

                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match');
                    return $this->redirectToRoute('app_user_profile');
                }

                // Hash and set new password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                
                $changes['password'] = ['changed' => true];
                
                $activityLogService->logActivity(
                    'PASSWORD_CHANGE',
                    'Changed password',
                    $user
                );
            }

            // Save changes
            $entityManager->flush();

            // Log changes
            if (!empty($changes)) {
                $activityLogService->logProfileUpdate($user, array_keys($changes));
                
                $this->addFlash('success', 'Profile updated successfully!');
                
                // If username changed, redirect to profile to refresh session
                if (isset($changes['username'])) {
                    return $this->redirectToRoute('app_user_profile');
                }
            } else {
                $this->addFlash('info', 'No changes were made');
            }

            return $this->redirectToRoute('app_user_profile');
        }

        $activityLogService->logActivity(
            'VIEW_PROFILE',
            'Viewed profile page',
            $user
        );

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user, ActivityLogService $activityLogService): Response
    {
        $activityLogService->logActivity(
            'VIEW_USER', 
            sprintf('Viewed user details for %s (ID: %d)', $user->getUsername(), $user->getId()), 
            $this->getUser()
        );

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogService $activityLogService
    ): Response
    {
        $oldUsername = $user->getUsername();
        $oldRoles = $user->getRoles();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password update if provided
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                
                // Log password change (without showing the actual password)
                $activityLogService->logActivity(
                    'PASSWORD_CHANGE',
                    sprintf('Changed password for user %s', $user->getUsername()),
                    $this->getUser()
                );
            }

            $newUsername = $user->getUsername();
            $newRoles = $user->getRoles();

            $changes = [];
            
            if ($oldUsername !== $newUsername) {
                $changes['username'] = [
                    'old' => $oldUsername,
                    'new' => $newUsername
                ];
            }
            
            $oldRolesSorted = $oldRoles;
            $newRolesSorted = $newRoles;
            sort($oldRolesSorted);
            sort($newRolesSorted);
            
            if ($oldRolesSorted != $newRolesSorted) {
                $changes['roles'] = [
                    'old' => $oldRoles,
                    'new' => $newRoles
                ];
                
                $activityLogService->logRoleChange(
                    $user,
                    $oldRoles,
                    $newRoles,
                    $this->getUser()
                );
            }

            $entityManager->flush();

            if (!empty($changes)) {
                $activityLogService->logUpdate('User', $user->getId(), $this->getUser(), $changes);
                
                // Check if editing own profile
                $currentUser = $this->getUser();
                if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
                    $activityLogService->logProfileUpdate($user, array_keys($changes));
                }
            }

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        $activityLogService->logActivity(
            'VIEW_EDIT_FORM', 
            sprintf('Viewed edit form for user %s (ID: %d)', $user->getUsername(), $user->getId()), 
            $this->getUser()
        );

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $userInfo = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles()
            ];

            $entityManager->remove($user);
            $entityManager->flush();

            $activityLogService->logDelete(
                'User', 
                $userInfo['id'], 
                $this->getUser(),
                sprintf('Deleted user %s', $userInfo['username'])
            );
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}