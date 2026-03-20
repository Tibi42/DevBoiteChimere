<?php

namespace App\Tests\Unit;

use App\Entity\Activity;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    public function testDefaultStatusIsPublished(): void
    {
        $activity = new Activity();

        $this->assertSame(Activity::STATUS_PUBLISHED, $activity->getStatus());
    }

    public function testSetStatusPending(): void
    {
        $activity = new Activity();
        $activity->setStatus(Activity::STATUS_PENDING);

        $this->assertSame(Activity::STATUS_PENDING, $activity->getStatus());
    }

    public function testSetStatusRejectsInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $activity = new Activity();
        $activity->setStatus('invalid-status');
    }

    public function testSetCreatedAtValueSetsCreatedAtAndUpdatedAt(): void
    {
        $activity = new Activity();

        $this->assertNull($activity->getCreatedAt());
        $this->assertNull($activity->getUpdatedAt());

        $activity->setCreatedAtValue();

        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getUpdatedAt());
        $this->assertGreaterThanOrEqual(
            $activity->getCreatedAt()->getTimestamp(),
            $activity->getUpdatedAt()->getTimestamp()
        );
    }

    public function testSetUpdatedAtValueSetsUpdatedAt(): void
    {
        $activity = new Activity();

        $this->assertNull($activity->getUpdatedAt());

        $activity->setUpdatedAtValue();

        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getUpdatedAt());
    }

    public function testSetStartAtConvertsToDateTimeImmutable(): void
    {
        $activity = new Activity();

        $startAt = new \DateTime('2026-03-20 10:00:00');
        $activity->setStartAt($startAt);

        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getStartAt());
        $this->assertSame($startAt->getTimestamp(), $activity->getStartAt()->getTimestamp());
    }
}

