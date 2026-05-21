<?php

namespace App\Tests\Unit;

use App\Repository\ArticleRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ArticleRepositoryTest extends TestCase
{
    public function testFindAllOrderByPosition(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $expected = ['article-1', 'article-2'];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($expected);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('orderBy')->with('a.position', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('addOrderBy')->with('a.id', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = new class($registry, $qb) extends ArticleRepository {
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

    public function testFindActiveOrderByPosition(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $expected = ['active-article-1'];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($expected);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('andWhere')->with('a.active = true')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('a.position', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('addOrderBy')->with('a.id', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = new class($registry, $qb) extends ArticleRepository {
            public function __construct(private readonly ManagerRegistry $registryMock, private readonly QueryBuilder $qbMock)
            {
                parent::__construct($this->registryMock);
            }

            public function createQueryBuilder(string $alias, string|null $indexBy = null): QueryBuilder
            {
                return $this->qbMock;
            }
        };

        $result = $repo->findActiveOrderByPosition();

        $this->assertSame($expected, $result);
    }
}
