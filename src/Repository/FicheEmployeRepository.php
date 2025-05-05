<?php

namespace App\Repository;

use App\Entity\FicheEmploye;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FicheEmploye>
 *
 * @method FicheEmploye|null find($id, $lockMode = null, $lockVersion = null)
 * @method FicheEmploye|null findOneBy(array $criteria, array $orderBy = null)
 * @method FicheEmploye[]    findAll()
 * @method FicheEmploye[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FicheEmployeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FicheEmploye::class);
    }

    public function search(string $search): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.employe', 'e')
            ->where('f.nom LIKE :search')
            ->orWhere('f.prenom LIKE :search')
            ->orWhere('f.cin LIKE :search')
            ->orWhere('f.email LIKE :search')
            ->orWhere('f.adresse LIKE :search')
            ->orWhere('f.city LIKE :search')
            ->orWhere('f.numTel LIKE :search')
            ->orWhere('e.username LIKE :search')
            ->orWhere('e.poste LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('f.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(FicheEmploye $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FicheEmploye $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return FicheEmploye[] Returns an array of FicheEmploye objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FicheEmploye
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
