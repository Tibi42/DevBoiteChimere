<?php

namespace App\Tests\Unit;

use App\Repository\CarouselSlideRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class CarouselSlideRepositoryTest extends TestCase
{
    public function testFindAllOrderByPosition(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $expected = ['slide-1', 'slide-2'];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($expected);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('orderBy')->with('c.position', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('addOrderBy')->with('c.id', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = new class($registry, $qb) extends CarouselSlideRepository {
            public function __construct(private readonly ManagerRegistry $registryMock, private readonly QueryBuilder $qbMock)
            {
                parent::__construct($this->registryMock);
            }

            public function createQueryBuilder(string $alias, string|null $indexBy = null): QueryBuilder
            {
                return $this->qbMock;
            }
        };

        $result = $repo->findAllOrderByPosition();

        $this->assertSame($expected, $result);
    }
}

