<?php

namespace App\Repository;

use App\Entity\NewsletterSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsletterSubscriber>
 */
class NewsletterSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscriber::class);
    }

    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByToken(string $token): ?NewsletterSubscriber
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * @return NewsletterSubscriber[]
     */
    public function findConfirmed(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.status = :status')
            ->setParameter('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->orderBy('n.confirmedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return NewsletterSubscriber[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
