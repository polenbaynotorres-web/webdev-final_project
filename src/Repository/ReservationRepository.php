<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Retrieve all reservations for Admin and Staff management.
     *
     * @return Reservation[]
     */
    public function findForManagement(
        ?string $search = null,
        ?string $status = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('r')
            ->addSelect('customer', 'vehicle', 'location')
            ->join('r.user', 'customer')
            ->join('r.vehicle', 'vehicle')
            ->join('r.location', 'location')
            ->orderBy('r.createdAt', 'DESC');

        if ($search !== null && trim($search) !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(customer.name) LIKE :search
                    OR LOWER(vehicle.name) LIKE :search
                    OR LOWER(location.name) LIKE :search
                    OR r.phone LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower(trim($search)) . '%'
                );
        }

        if (in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'], true)) {
            $queryBuilder
                ->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Retrieve only the reservations belonging to one customer.
     *
     * @return Reservation[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('vehicle', 'location')
            ->join('r.vehicle', 'vehicle')
            ->join('r.location', 'location')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
