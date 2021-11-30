<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Form\PostFormType;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PostRepository;
use Twig\Environment;


class PostController extends AbstractController
{

    private $twig;
    private $entityManager;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'homepage')]
    public function index(Request $request, PostRepository $postRepository): Response
    {
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {

                    $this->entityManager->persist($post);
                    $this->entityManager->flush();

                    return $this->redirectToRoute('homepage');
        }

        return new Response($this->twig->render('post/index.html.twig', [
                        'posts' => $postRepository->findAll(),
                        'post_form' => $form->createView()
                    ]));
    }


    #[Route('/post/{slug}', name: 'post')]
    public function show(Request $request, Post $post, CommentRepository $commentRepository, PostRepository $postRepository, string $photoDir): Response
    {

        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $comment->setPost($post);

                    if ($photo = $form['photo']->getData()) {
                        $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                        try {
                                $photo->move($photoDir, $filename);
                            } catch (FileException $e) {

                            }
                         $comment->setPhotoFilename($filename);
                    }

                    $this->entityManager->persist($comment);
                    $this->entityManager->flush();

                    return $this->redirectToRoute('post', ['slug' => $post->getSlug()]);
        }


        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($post, $offset);

        return new Response($this->twig->render('post/show.html.twig', [
            'posts' => $postRepository->findAll(),
            'post' => $post,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView()
        ]));
    }
}
