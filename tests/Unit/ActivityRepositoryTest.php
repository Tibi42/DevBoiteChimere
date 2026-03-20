<?php

namespace App\Tests\Unit;

use App\Repository\ActivityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ActivityRepositoryTest extends TestCase
{
    private function makeRepoWithQueryBuilder(ManagerRegistry $registry, QueryBuilder $qb): ActivityRepository
    {
        return new class($registry, $qb) extends ActivityRepository {
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

    public function testFindBetweenBuildsQueryAndReturnsResults(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $start = new \DateTimeImmutable('2026-03-01 00:00:00');
        $end = new \DateTimeImmutable('2026-03-31 23:59:59');
        $expected = ['activity-1', 'activity-2'];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expected);

        $qb = $this->createMock(QueryBuilder::class);

        $expectedAndWheres = [
            'a.startAt >= :start',
            'a.startAt <= :end',
            'a.status = :status',
        ];
        $andWhereCallIndex = 0;

        $qb->expects($this->exactly(3))
            ->method('andWhere')
            ->willReturnCallback(function (string $where) use (&$andWhereCallIndex, $expectedAndWheres, $qb) {
                $this->assertSame($expectedAndWheres[$andWhereCallIndex], $where);
                $andWhereCallIndex++;

                return $qb;
            });

        $expectedParameters = [
            ['start', $start],
            ['end', $end],
            ['status', \App\Entity\Activity::STATUS_PUBLISHED],
        ];
        $setParameterCallIndex = 0;

        $qb->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnCallback(function (string $key, mixed $value) use (&$setParameterCallIndex, $expectedParameters, $qb) {
                $this->assertSame($expectedParameters[$setParameterCallIndex][0], $key);
                $this->assertSame($expectedParameters[$setParameterCallIndex][1], $value);
                $setParameterCallIndex++;

                return $qb;
            });

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('a.startAt', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->makeRepoWithQueryBuilder($registry, $qb);

        $result = $repo->findBetween($start, $end);

        $this->assertSame($expected, $result);
    }

    public function testFindAllOrderByStartDescWithoutStatus(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $expected = ['activity-1'];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($expected);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('leftJoin')->with('a.proposedBy', 'u')->willReturnSelf();
        $qb->expects($this->once())->method('addSelect')->with('u')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('a.startAt', 'DESC')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->never())->method('setParameter');
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->makeRepoWithQueryBuilder($registry, $qb);

        $result = $repo->findAllOrderByStartDesc(null);

        $this->assertSame($expected, $result);
    }

    public function testFindAllOrderByStartDescWithValidStatus(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $expected = ['activity-1'];
        $status = \App\Entity\Activity::STATUS_PENDING;

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($expected);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('leftJoin')->with('a.proposedBy', 'u')->willReturnSelf();
        $qb->expects($this->once())->method('addSelect')->with('u')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('a.startAt', 'DESC')->willReturnSelf();

        $qb->expects($this->once())->method('andWhere')->with('a.status = :status')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('status', $status)->willReturnSelf();

        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->makeRepoWithQueryBuilder($registry, $qb);

        $result = $repo->findAllOrderByStartDesc($status);

        $this->assertSame($expected, $result);
    }

    public function testFindAllOrderByStartDescRejectsInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $registry = $this->createMock(ManagerRegistry::class);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('leftJoin')->with('a.proposedBy', 'u')->willReturnSelf();
        $qb->expects($this->once())->method('addSelect')->with('u')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('a.startAt', 'DESC')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->never())->method('setParameter');
        $qb->expects($this->never())->method('getQuery');

        $repo = $this->makeRepoWithQueryBuilder($registry, $qb);

        $repo->findAllOrderByStartDesc('invalid-status');
    }

    public function testFindTopProposersBetweenBuildsQueryAndReturnsArrayResult(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $start = new \DateTimeImmutable('2026-03-01 00:00:00');
        $end = new \DateTimeImmutable('2026-04-01 00:00:00');
        $limit = 3;

        $expected = [
            ['id' => 1, 'username' => 'bob', 'email' => 'bob@example.com', 'activityCount' => 10],
        ];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getArrayResult')->willReturn($expected);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('u.id as id')->willReturnSelf();
        $expectedAddSelects = [
            'u.username as username',
            'u.email as email',
            'COUNT(a.id) as activityCount',
        ];
        $addSelectCallIndex = 0;
        $qb->expects($this->exactly(3))
            ->method('addSelect')
            ->willReturnCallback(function (mixed $select) use (&$addSelectCallIndex, $expectedAddSelects, $qb) {
                $this->assertSame($expectedAddSelects[$addSelectCallIndex], $select);
                $addSelectCallIndex++;

                return $qb;
            });

        $qb->expects($this->once())->method('innerJoin')->with('a.createdBy', 'u')->willReturnSelf();

        $expectedAndWheres = [
            'a.createdAt >= :start',
            'a.createdAt < :end',
        ];
        $andWhereCallIndex = 0;
        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function (string $where) use (&$andWhereCallIndex, $expectedAndWheres, $qb) {
                $this->assertSame($expectedAndWheres[$andWhereCallIndex], $where);
                $andWhereCallIndex++;

                return $qb;
            });

        $qb->expects($this->once())->method('groupBy')->with('u.id')->willReturnSelf();
        $expectedAddGroupBys = [
            'u.username',
            'u.email',
        ];
        $addGroupByCallIndex = 0;
        $qb->expects($this->exactly(2))
            ->method('addGroupBy')
            ->willReturnCallback(function (string $groupBy) use (&$addGroupByCallIndex, $expectedAddGroupBys, $qb) {
                $this->assertSame($expectedAddGroupBys[$addGroupByCallIndex], $groupBy);
                $addGroupByCallIndex++;

                return $qb;
            });

        $qb->expects($this->once())->method('orderBy')->with('activityCount', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();

        $expectedParameters = [
            ['start', $start],
            ['end', $end],
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

        $repo = $this->makeRepoWithQueryBuilder($registry, $qb);

        $result = $repo->findTopProposersBetween($start, $end, $limit);

        $this->assertSame($expected, $result);
    }
}

