<?php

namespace App\EventSubscriber;

use App\Repository\PostRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{

    private $twig;
    private $postRepository;

    public function __construct(Environment $twig, PostRepository $postRepository)
    {
        $this->twig = $twig;
        $this->postRepository = $postRepository;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $this->twig->addGlobal('posts', $this->postRepository->findAll());
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
