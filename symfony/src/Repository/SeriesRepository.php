<?php

namespace App\Repository;

use App\Entity\Series;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Series>
 *
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends ServiceEntityRepository
{


    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);

    }//end __construct()


    public function seriesInfo()
    {
        return $this->createQueryBuilder('s')->select(
            's.id as id, s.title as title, s.poster, s.plot as plot, 
            COUNT(DISTINCT se.number) as season_count, COUNT(e.number) as episode_count'
        )->leftJoin('App:Season', 'se', 'WITH', 's = se.series')->leftJoin('App:Episode', 'e', 'WITH', 'se = e.season')->leftJoin('s.genre', 'genre', 'WITH')->leftJoin('s.user', 'user', 'WITH')->groupBy('s.id');

    }//end seriesInfo()


    public function seriesInfoById($id)
    {
        return $this->createQueryBuilder('s')->select('s')->leftJoin('s.seasons', 'seasons')->leftJoin('seasons.episodes', 'episode')->leftJoin('s.genre', 'genre', 'WITH')->leftJoin('s.user', 'user', 'WITH')->where('s.id = :id')->setParameter('id', $id)->orderBy('episode.number', 'ASC')->getQuery()->getOneOrNullResult();

    }//end seriesInfoById()


    public function seriesInfoByIdAndSeason($id, $num)
    {
        return $this->createQueryBuilder('s')->select('s')->leftJoin('s.seasons', 'seasons')->leftJoin('seasons.episodes', 'episode')->leftJoin('s.genre', 'genre', 'WITH')->leftJoin('s.user', 'user', 'WITH')->where('s.id = :id')->andWhere('seasons.number = :num')->setParameter('id', $id)->setParameter('num', $num)->getQuery()->getOneOrNullResult();

    }//end seriesInfoByIdAndSeason()


}//end class
