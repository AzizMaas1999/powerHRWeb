<?php

namespace App\Repository;

use App\Entity\Departement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Departement>
 */
class DepartementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Departement::class);
    }

    public function searchDepartements(?string $query)
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.entreprise', 'e')
            ->orderBy('d.id', 'DESC');

        if ($query) {
            $qb->where('d.nom LIKE :query')
               ->orWhere('d.description LIKE :query')
               ->orWhere('e.nom LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        return $qb;
    }

    //    /**
    //     * @return Departement[] Returns an array of Departement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Departement
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
