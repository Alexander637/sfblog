<?php

namespace App\EntityListener;

use App\Entity\Post;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostEntityListener
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function prePersist(Post $post, LifecycleEventArgs $event)
    {
        $post->computeSlug($this->slugger);
    }

    public function preUpdate(Post $post, LifecycleEventArgs $event)
    {
        $post->computeSlug($this->slugger);
    }
}
