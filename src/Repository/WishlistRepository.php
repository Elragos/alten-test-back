<?php

namespace App\Repository;

use App\Entity\Wishlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * Wishlist repository, used to find and save wishlist.
 * @extends ServiceEntityRepository<Wishlist>
 */
class WishlistRepository extends ServiceEntityRepository
{
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wishlist::class);
    }

    /**
     * Save wishlist to database.
     * @param Wishlist $wishlist Desired wishlist
     * @return void
     */
    public function save(Wishlist $wishlist): void{
        $this->getEntityManager()->persist($wishlist);
        $this->getEntityManager()->flush();
    }
}
