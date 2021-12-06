<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findAll()
    {
        return $this->findBy([], ['title' => 'ASC']);
    }

    public function findMy($userId)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT p FROM App\Entity\Post p WHERE p.admin= " . $userId);
        $myPosts = $query->getResult();
        return $myPosts;
    }


}
