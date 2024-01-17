<?php

namespace App\Repository;

use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 *
 * @method Rating|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rating|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rating[]    findAll()
 * @method Rating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }
    public function getRatingUserConnectAndAllRatingComments($userId, $seriesId)
    {
        return $this->createQueryBuilder('r')->select('r')->leftJoin('r.user', 'user', 'WITH')->leftJoin('r.series', 'series', 'WITH')->where('user.id = :userId')->andWhere('user.id IS NOT NULL')->orWhere('series.id = :seriesId')->orWhere('series.id=:seriesId')->setParameter('userId', $userId)->setParameter('seriesId', $seriesId)->getQuery()->getResult();
    }//end getRatingUserConnect()
}
