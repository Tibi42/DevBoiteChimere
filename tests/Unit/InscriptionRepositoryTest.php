<?php

namespace App\Tests\Unit;

use App\Repository\InscriptionRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class InscriptionRepositoryTest extends TestCase
{
    private function makeRepo(ManagerRegistry $registry, QueryBuilder $qb): InscriptionRepository
    {
        return new class($registry, $qb) extends InscriptionRepository {
            public function __construct(
                private readonly ManagerRegistry $registryMock,
                private readonly QueryBuilder $qbMock,
            ) {
                parent::__construct($this->registryMock);
            }

            public function createQueryBuilder(string $alias, string|null $indexBy = null): QueryBuilder
            {
                return $this->qbMock;
            }
        };
    }

    public function testHasAlreadyRegisteredReturnsTrueWhenScalarIsPositive(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $activityId = 12;
        $email = 'alice@example.com';

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('1');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('COUNT(i.id)')->willReturnSelf();
        $expectedAndWheres = [
            'i.activity = :activityId',
            'i.participantEmail = :email',
        ];
        $andWhereCallIndex = 0;
        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function (string $where) use (&$andWhereCallIndex, $expectedAndWheres, $qb) {
                $this->assertSame($expectedAndWheres[$andWhereCallIndex], $where);
                $andWhereCallIndex++;

                return $qb;
            });

        $expectedParameters = [
            ['activityId', $activityId],
            ['email', $email],
        ];
        $setParameterCallIndex = 0;
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function (string $key, mixed $value) use (&$setParameterCallIndex, $expectedParameters, $qb) {
                $this->assertSame($expectedParameters[$setParameterCallIndex][0], $key);
                $this->assertSame($expectedParameters[$setParameterCallIndex][1], $value);
                $setParameterCallIndex++;

                return $qb;
            });

        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->makeRepo($registry, $qb);

        $this->assertTrue($repo->hasAlreadyRegistered($activityId, $email));
    }

    public function testHasAlreadyRegisteredReturnsFalseWhenScalarIsZero(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $activityId = 12;
        $email = 'alice@example.com';

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('0');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('COUNT(i.id)')->willReturnSelf();
        $expectedAndWheres = [
            'i.activity = :activityId',
            'i.participantEmail = :email',
        ];
        $andWhereCallIndex = 0;
        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function (string $where) use (&$andWhereCallIndex, $expectedAndWheres, $qb) {
                $this->assertSame($expectedAndWheres[$andWhereCallIndex], $where);
                $andWhereCallIndex++;

                return $qb;
            });

        $expectedParameters = [
            ['activityId', $activityId],
            ['email', $email],
        ];
        $setParameterCallIndex = 0;
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function (string $key, mixed $value) use (&$setParameterCallIndex, $expectedParameters, $qb) {
                $this->assertSame($expectedParameters[$setParameterCallIndex][0], $key);
                $this->assertSame($expectedParameters[$setParameterCallIndex][1], $value);
                $setParameterCallIndex++;

                return $qb;
            });

        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->makeRepo($registry, $qb);

        $this->assertFalse($repo->hasAlreadyRegistered($activityId, $email));
    }

    public function testCountAllReturnsIntegerScalar(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $expected = 42;

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $expected);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('COUNT(i.id)')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->makeRepo($registry, $qb);

        $this->assertSame($expected, $repo->countAll());
    }
}

