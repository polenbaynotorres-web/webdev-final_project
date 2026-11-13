<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Search accounts of a specific role.
     * Deleted accounts are excluded.
     *
     * @return User[]
     */
    public function searchByRole(string $role, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->andWhere('u.status != :deleted')
            ->setParameter('role', $role)
            ->setParameter('deleted', User::STATUS_DELETED)
            ->orderBy('u.createdAt', 'DESC');

        $search = trim($search);

        if ($search !== '') {
            $qb->andWhere(
                'LOWER(u.name) LIKE :search
                 OR LOWER(u.username) LIKE :search
                 OR LOWER(u.email) LIKE :search'
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneByIdentifier(string $identifier): ?User
    {
        $identifier = mb_strtolower(trim($identifier));

        return $this->createQueryBuilder('u')
            ->where('LOWER(u.email) = :identifier')
            ->orWhere('LOWER(u.username) = :identifier')
            ->setParameter('identifier', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
