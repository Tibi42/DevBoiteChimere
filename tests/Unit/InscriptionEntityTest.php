<?php

namespace App\Tests\Unit;

use App\Entity\Activity;
use App\Entity\Inscription;
use PHPUnit\Framework\TestCase;

class InscriptionEntityTest extends TestCase
{
    public function testSettersAndCreatedAtLifecycle(): void
    {
        $activity = new Activity();

        $inscription = new Inscription();
        $inscription->setActivity($activity);
        $inscription->setParticipantName('Alice');
        $inscription->setParticipantEmail('alice@example.com');

        $this->assertSame($activity, $inscription->getActivity());
        $this->assertSame('Alice', $inscription->getParticipantName());
        $this->assertSame('alice@example.com', $inscription->getParticipantEmail());

        $this->assertNull($inscription->getCreatedAt());
        $inscription->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $inscription->getCreatedAt());
    }
}

