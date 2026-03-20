<?php

namespace App\Tests\Unit;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ResetPasswordRequestEntityTest extends TestCase
{
    public function testConstructorAndExpiration(): void
    {
        $user = new User();
        $expiresAt = new \DateTimeImmutable('+1 day');

        $request = new ResetPasswordRequest(
            $user,
            $expiresAt,
            'selector-123',
            'hashed-token-abc'
        );

        $this->assertSame($user, $request->getUser());
        $this->assertInstanceOf(\DateTimeInterface::class, $request->getRequestedAt());
        $this->assertSame($expiresAt, $request->getExpiresAt());
        $this->assertSame('hashed-token-abc', $request->getHashedToken());
        $this->assertFalse($request->isExpired());
    }

    public function testIsExpiredWhenInPast(): void
    {
        $user = new User();
        $expiresAt = new \DateTimeImmutable('-10 minutes');

        $request = new ResetPasswordRequest(
            $user,
            $expiresAt,
            'selector-123',
            'hashed-token-abc'
        );

        $this->assertTrue($request->isExpired());
    }
}

