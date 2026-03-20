<?php

namespace App\Tests\Unit;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ResetPasswordRequestRepositoryTest extends TestCase
{
    public function testCreateResetPasswordRequest(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $repo = new ResetPasswordRequestRepository($registry);

        $user = new User();
        $expiresAt = new \DateTimeImmutable('+30 minutes');

        $request = $repo->createResetPasswordRequest(
            $user,
            $expiresAt,
            'selector-123',
            'hashed-token-abc'
        );

        $this->assertInstanceOf(ResetPasswordRequest::class, $request);
        $this->assertSame($user, $request->getUser());
        $this->assertSame($expiresAt, $request->getExpiresAt());
        $this->assertSame('hashed-token-abc', $request->getHashedToken());
        $this->assertFalse($request->isExpired());
    }
}

