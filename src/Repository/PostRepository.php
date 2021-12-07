<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{

    public const PAGINATOR_PER_PAGE = 10;


    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function getPostPaginator($user, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('a')
            ->andWhere('a.admin = :admin')
            ->setParameter('admin', $user)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)

            ->getQuery();

        return new Paginator($query);
    }
    public function getAllPostsPaginator(int $offset): Paginator
    {

        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT p FROM App\Entity\Post p ORDER BY p.id asc")
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset);

        return new Paginator($query);

    }

    public function findAll()
    {
        return $this->findBy([], ['title' => 'ASC']);
    }

    public function findLast()
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT p FROM App\Entity\Post p ORDER BY p.id desc")
            ->setMaxResults(10);

        $lastPosts = $query->getResult();

        return $lastPosts;
    }

    public function search($data)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT p FROM App\Entity\Post p WHERE p.title LIKE :data")
            ->setParameter('data', $data);

        $search = $query->getResult();

        return $search;

    }


}
