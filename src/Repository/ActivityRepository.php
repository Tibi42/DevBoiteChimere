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
    public function findBetween(\DateTimeInterface $start, \DateTimeInterface $end, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.startAt >= :start')
            ->andWhere('a.startAt <= :end')
            ->andWhere('a.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', Activity::STATUS_PUBLISHED)
            ->orderBy('a.startAt', 'ASC');

        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne toutes les activités triées par date de début (plus récentes en premier pour la gestion).
     *
     * @return Activity[]
     */
    public function findAllOrderByStartDesc(?string $status = null, ?string $type = null, ?string $location = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.proposedBy', 'u')
            ->addSelect('u')
            ->orderBy('a.startAt', 'DESC');

        if ($status !== null) {
            if (!in_array($status, [Activity::STATUS_PUBLISHED, Activity::STATUS_PENDING], true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid status "%s". Allowed values: "%s", "%s".',
                    $status,
                    Activity::STATUS_PUBLISHED,
                    Activity::STATUS_PENDING
                ));
            }
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        if ($location !== null) {
            $qb->andWhere('a.location = :location')
               ->setParameter('location', $location);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne le top des utilisateurs ayant le plus d'activités créées
     * sur la période [start; end[ (end exclus).
     *
     * @return array<int, array{id:int, username:string|null, email:string|null, activityCount:int}>
     */
    public function findTopProposersBetween(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        int $limit = 5
    ): array {
        return $this->createQueryBuilder('a')
            ->select('u.id as id')
            ->addSelect('u.username as username')
            ->addSelect('u.email as email')
            ->addSelect('COUNT(a.id) as activityCount')
            ->innerJoin('a.createdBy', 'u')
            ->andWhere('a.createdAt >= :start')
            ->andWhere('a.createdAt < :end')
            ->groupBy('u.id')
            ->addGroupBy('u.username')
            ->addGroupBy('u.email')
            ->orderBy('activityCount', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getArrayResult();
    }

    public function countPendingApproval(): int
    {
        return $this->count(['status' => Activity::STATUS_PENDING]);
    }
}
