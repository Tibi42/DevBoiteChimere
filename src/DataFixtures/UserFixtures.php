<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['user'];
    }

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email'    => 'guillaume.pecquet@gmail.com',
                'username' => 'Dyonisos',
                'roles'    => ['ROLE_SUPER_ADMIN'],
                'password' => '112358134AaBb&',
            ],
            [
                'email'    => 'admin@boitechimere.fr',
                'username' => 'admin',
                'roles'    => ['ROLE_ADMIN'],
                'password' => 'admin123',
            ],
            [
                'email'    => 'user@boitechimere.fr',
                'username' => 'user',
                'roles'    => ['ROLE_USER'],
                'password' => 'user123',
            ],
        ];

        foreach ($users as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setRoles($data['roles']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
