<?php

namespace App\Repository;

use App\Entity\Paie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @extends ServiceEntityRepository<Paie>
 */
class PaieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paie::class);
    }

    /**
     * Find all payments sorted by year and month in descending order
     */
    public function findAllOrderedByYearAndMonthDesc(): array
    {
        $moisOrder = [
            'Janvier' => 1, 'Février' => 2, 'Mars' => 3, 'Avril' => 4,
            'Mai' => 5, 'Juin' => 6, 'Juillet' => 7, 'Août' => 8,
            'Septembre' => 9, 'Octobre' => 10, 'Novembre' => 11, 'Décembre' => 12
        ];

        $paies = $this->findAll();
        
        usort($paies, function($a, $b) use ($moisOrder) {
            // Compare years first (descending)
            $yearA = (int)$a->getAnnee();
            $yearB = (int)$b->getAnnee();
            
            if ($yearA !== $yearB) {
                return $yearB <=> $yearA; // Descending
            }
            
            // If years are equal, compare months (descending)
            $monthA = $moisOrder[$a->getMois()] ?? 0;
            $monthB = $moisOrder[$b->getMois()] ?? 0;
            
            return $monthB <=> $monthA; // Descending
        });
        
        return $paies;
    }

    /**
     * Search payments by employee username
     */
    public function searchByUsername(string $username): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('App\Entity\Pointage', 'pt', Join::WITH, 'pt.paie = p.id')
            ->innerJoin('App\Entity\Employe', 'e', Join::WITH, 'pt.employe = e.id')
            ->andWhere('e.username LIKE :username')
            ->setParameter('username', '%' . $username . '%')
            ->groupBy('p.id')  // Group to avoid duplicate payments
            ->orderBy('p.annee', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Paie[] Returns an array of Paie objects
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

    //    public function findOneBySomeField($value): ?Paie
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
