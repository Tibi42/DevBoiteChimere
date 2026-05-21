<?php
/**
 * Standalone fixtures loader for production
 * Usage: php bin/console load-fixtures
 */

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'load-fixtures',
    description: 'Load fixture data (users, activities, carousel slides)',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Loading fixtures...');

        // Load users
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
            $this->em->persist($user);
            $output->writeln('  ✓ User: ' . $data['email']);
        }

        $this->em->flush();
        $output->writeln('<info>Fixtures loaded successfully!</info>');
        return Command::SUCCESS;
    }
}
