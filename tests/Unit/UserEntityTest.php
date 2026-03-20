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
}

