<?php

namespace App\Tests\Unit;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testGetUserIdentifierUsesEmail(): void
    {
        $user = new User();
        $user->setEmail('bob@example.com');

        $this->assertSame('bob@example.com', $user->getUserIdentifier());
    }

    public function testDefaultSuspendedIsFalse(): void
    {
        $user = new User();

        $this->assertFalse($user->isSuspended());
    }

    public function testSetSuspended(): void
    {
        $user = new User();
        $user->setSuspended(true);

        $this->assertTrue($user->isSuspended());
    }

    public function testUsernameSetterAndGetter(): void
    {
        $user = new User();
        $user->setUsername('chimere42');

        $this->assertSame('chimere42', $user->getUsername());
    }

    public function testEraseCredentialsDoesNotThrow(): void
    {
        $user = new User();
        $user->eraseCredentials();

        $this->assertTrue(true);
    }

    public function testSetPasswordAndGetPassword(): void
    {
        $user = new User();
        $user->setPassword('hashed_pwd');

        $this->assertSame('hashed_pwd', $user->getPassword());
    }
}

