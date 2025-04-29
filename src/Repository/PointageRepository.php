<?php

namespace App\Repository;

use App\Entity\Pointage;
use App\Entity\Employe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @extends ServiceEntityRepository<Pointage>
 */
class PointageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pointage::class);
    }

    /**
     * Recherche les pointages d'un employé pour un mois et une année spécifiques
     * 
     * @param Employe $employe L'employé dont on recherche les pointages
     * @param int $month Le numéro du mois (1-12)
     * @param int $year L'année
     * @return Pointage[] Un tableau de pointages
     */
    public function findByEmployeAndMonthYear(Employe $employe, int $month, int $year): array
    {
        $firstDayOfMonth = new DateTime("$year-$month-01");
        $lastDayOfMonth = clone $firstDayOfMonth;
        $lastDayOfMonth->modify('last day of this month');
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.employe = :employe')
            ->andWhere('p.date >= :startDate')
            ->andWhere('p.date <= :endDate')
            ->setParameter('employe', $employe)
            ->setParameter('startDate', $firstDayOfMonth)
            ->setParameter('endDate', $lastDayOfMonth)
            ->orderBy('p.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find pointages by employee and date range
     */
    public function findPointagesByEmployeAndDateRange(Employe $employe, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.employe = :employe')
            ->andWhere('p.date >= :startDate')
            ->andWhere('p.date <= :endDate')
            ->setParameter('employe', $employe)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('p.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Pointage[] Returns an array of Pointage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Pointage
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
