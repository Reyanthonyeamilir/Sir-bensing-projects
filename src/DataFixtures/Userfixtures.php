<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class Userfixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($admin,'passwordadmin');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

         $staff = new User();
        $staff->setUsername('staff');
        $staff->setRoles(['ROLE_staff']);
        $hashedPassword = $this->passwordHasher->hashPassword($staff,'passwordstaff');
        $staff->setPassword($hashedPassword);

         $manager->persist($staff);
        $manager->flush();
    }
}