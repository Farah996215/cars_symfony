<?php

namespace App\Repository;

use App\Entity\Car;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    public function findAllOrderedByBrand(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.brand', 'ASC')
            ->addOrderBy('c.model', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByBrand(string $brand): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.brand = :brand')
            ->setParameter('brand', $brand)
            ->orderBy('c.model', 'ASC')
            ->getQuery()
            ->getResult();
    }
}