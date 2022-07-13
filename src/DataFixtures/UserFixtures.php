<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Data fixtures for used for uploading test user (credentials open for everyone)
 */
class UserFixtures extends Fixture
{
    public function __construct(UserPasswordEncoderInterface $passwordHasher)
        {
            $this->passwordHasher = $passwordHasher;
        }

    public function load(ObjectManager $manager)
    {
        $manager->persist($user);

        $userTest = new User();
        $userTest->setEmail('test@test.pl');
        $userTest->setRoles(['ROLE_TEST']);
        $userTest->setPassword($this->passwordHasher->encodePassword(
                         $userTest,
                         'QWEasd'
                    ));

        $manager->persist($userTest);
        
        $manager->flush();
    }
}