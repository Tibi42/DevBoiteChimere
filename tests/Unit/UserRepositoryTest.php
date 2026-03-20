<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class UserRepositoryTest extends TestCase
{
    public function testUpgradePasswordThrowsUnsupportedUserWhenWrongType(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repo = new class($registry, $em) extends UserRepository {
            public function __construct(
                private readonly ManagerRegistry $registryMock,
                private readonly EntityManagerInterface $emMock
            ) {
                parent::__construct($this->registryMock);
            }

            protected function getEntityManager(): EntityManagerInterface
            {
                return $this->emMock;
            }
        };

        $wrongUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);

        $repo->upgradePassword($wrongUser, 'new-hash');
    }

    public function testUpgradePasswordPersistsAndFlushes(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repo = new class($registry, $em) extends UserRepository {
            public function __construct(
                private readonly ManagerRegistry $registryMock,
                private readonly EntityManagerInterface $emMock
            ) {
                parent::__construct($this->registryMock);
            }

            protected function getEntityManager(): EntityManagerInterface
            {
                return $this->emMock;
            }
        };

        $user = new User();

        $em->expects($this->once())->method('persist')->with($user);
        $em->expects($this->once())->method('flush');

        $repo->upgradePassword($user, 'new-hash');

        $this->assertSame('new-hash', $user->getPassword());
    }
}

