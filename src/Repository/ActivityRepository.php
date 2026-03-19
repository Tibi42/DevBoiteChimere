<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * Retourne les activités dont la date de début est entre $start et $end (inclus).
     *
     * @return Activity[]
     */
    public function findBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.startAt >= :start')
            ->andWhere('a.startAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('a.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les activités triées par date de début (plus récentes en premier pour la gestion).
     *
     * @return Activity[]
     */
    public function findAllOrderByStartDesc(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
