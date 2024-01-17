<?php

namespace App\Repository;

use App\Entity\Series;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineExtensions\Query\Mysql\Rand;

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


    public function seriesInfo($seed)
    {
        return $this->createQueryBuilder('s')->select(
            's.id as id, s.title as title, s.poster, s.plot as plot, 
            COUNT(DISTINCT se.number) as season_count, COUNT(e.number) as episode_count, s.youtubeTrailer, AVG(rating.value) as note'
        )->leftJoin('App:Season', 'se', 'WITH', 's = se.series')
        ->leftJoin('App:Episode', 'e', 'WITH', 'se = e.season')
        ->leftJoin('s.genre', 'genre', 'WITH')
        ->leftJoin('s.user', 'user', 'WITH')
        ->leftJoin('App:Rating', 'rating', 'WITH', 's = rating.series')
        ->groupBy('s.id')->orderBy('RAND(' . $seed . ')');
    }//end seriesInfo()

    public function seriesEpisodesCount($user)
    {
        return $this->createQueryBuilder('s')->select(
            's.id as id, s.title as title, s.poster, s.plot as plot, 
            COUNT(DISTINCT se.number) as season_count, COUNT(Distinct(e.id)) as episode_count, s.youtubeTrailer'
        )->leftJoin('App:Season', 'se', 'WITH', 's = se.series')->leftJoin('App:Episode', 'e', 'WITH', 'se = e.season')->leftJoin('s.genre', 'genre', 'WITH')->leftJoin('s.user', 'user', 'WITH')->groupBy('s.id')->where('user.id=:user')->setParameter('user', $user)->getQuery()->getResult();
    }//end seriesAllInfo()

    public function seriesEpisodeCountView($user)
    {
        return $this->createQueryBuilder('s')->select(
            's.id as id, s.title as title, COUNT(Distinct(e.id)) as episode_count_view'
        )->leftJoin('App:Season', 'se', 'WITH', 's = se.series')->leftJoin('App:Episode', 'e', 'WITH', 'se = e.season')->leftJoin('s.user', 'user', 'WITH')->groupBy('s.id')->where(':user MEMBER OF e.user')->setParameter('user', $user)->getQuery()->getResult();
    }//end seriesAllInfo()

    public function seriesInfoById($id)
    {
        return $this->createQueryBuilder('s')->select('s')->leftJoin('s.seasons', 'seasons')->leftJoin('seasons.episodes', 'episode')->leftJoin('s.genre', 'genre', 'WITH')->leftJoin('s.user', 'user', 'WITH')->where('s.id = :id')->setParameter('id', $id)->orderBy('episode.number', 'ASC')->getQuery()->getOneOrNullResult();
    }//end seriesInfoById()


    public function seriesInfoByIdAndSeason($id, $num)
    {
        return $this->createQueryBuilder('s')->select('s')->leftJoin('s.seasons', 'seasons')->leftJoin('seasons.episodes', 'episode')->leftJoin('s.genre', 'genre', 'WITH')->leftJoin('s.user', 'user', 'WITH')->where('s.id = :id')->andWhere('seasons.number = :num')->setParameter('id', $id)->setParameter('num', $num)->getQuery()->getOneOrNullResult();
    }//end seriesInfoByIdAndSeason()
    public function seriesInfoByIdAndSeasonAndEpisode($id, $num, $ep)
    {
        return $this->createQueryBuilder('episode')->select('s')->leftJoin('s.seasons', 'seasons')->leftJoin('seasons.episodes', 'episode')->leftJoin('s.genre', 'genre', 'WITH')->leftJoin('s.user', 'user', 'WITH')->where('s.id = :id')->andWhere('seasons.number = :num')->andWhere('episode.number = :ep')->setParameter('id', $id)->setParameter('num', $num)->setParameter('ep', $ep)->getQuery()->getOneOrNullResult();
    }//end seriesInfoByIdAndSeasonAndEpisode()
}//end class
