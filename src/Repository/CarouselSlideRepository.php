<?php

namespace App\Repository;

use App\Entity\CarouselSlide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CarouselSlide>
 */
class CarouselSlideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarouselSlide::class);
    }

    /**
     * @return CarouselSlide[]
     */
    public function findAllOrderByPosition(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CarouselSlide[]
     */
    public function findActiveOrderByPosition(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.active = true')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
