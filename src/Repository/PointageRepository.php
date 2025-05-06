<?php

namespace App\Repository;

use App\Entity\Pointage;
use App\Entity\Employe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @extends ServiceEntityRepository<Pointage>
 *
 * @method Pointage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pointage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pointage[]    findAll()
 * @method Pointage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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

    /**
     * Find pointages by date range
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.date >= :startDate')
            ->andWhere('p.date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('p.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate total hours worked for an employee
     * 
     * @param int $employeId The employee ID
     * @return float Total hours worked
     */
    public function calculateTotalHoursForUser(int $employeId): float
    {
        $pointages = $this->findBy(['employe' => $employeId]);
        
        $totalHours = 0;
        foreach ($pointages as $pointage) {
            if ($pointage->getHeureEntree() && $pointage->getHeureSortie()) {
                $heureEntree = $pointage->getHeureEntree();
                $heureSortie = $pointage->getHeureSortie();
                
                // Calculate hours difference
                $diff = $heureEntree->diff($heureSortie);
                $hours = $diff->h + ($diff->i / 60);
                
                $totalHours += $hours;
            }
        }
        
        return round($totalHours, 2);
    }
    
    /**
     * Find active pointage for a user (today with no exit time)
     *
     * @param int $employeId The employee ID
     * @return Pointage|null The active pointage or null if none
     */
    public function findActivePointageForUser(int $employeId): ?Pointage
    {
        $today = new \DateTime('now');
        $today->setTime(0, 0, 0);
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.employe = :employeId')
            ->andWhere('p.date = :today')
            ->andWhere('p.heureSortie IS NULL')
            ->setParameter('employeId', $employeId)
            ->setParameter('today', $today)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Find recent pointages for a user
     *
     * @param int $employeId The employee ID
     * @param int $limit Maximum number of results to return
     * @return Pointage[] Array of recent pointages
     */
    public function findRecentPointagesForUser(int $employeId, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.employe = :employeId')
            ->setParameter('employeId', $employeId)
            ->orderBy('p.date', 'DESC')
            ->setMaxResults($limit)
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
