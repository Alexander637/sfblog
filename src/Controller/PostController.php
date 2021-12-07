<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Admin;
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Form\PostFormType;
use App\Repository\AdminRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PostRepository;
use Twig\Environment;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;


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
    public function index( Request $request, PostRepository $postRepository, AdminRepository $adminRepository): Response
    {
        $admin = $this->getUser();

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $postRepository->getAllPostsPaginator($offset);

        return new Response($this->twig->render('post/index.html.twig', [
            'posts' => $paginator,
            'admins' =>$adminRepository->findAll(),
            'last_posts' => $postRepository->findLast(),
            'previous' => $offset - PostRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + PostRepository::PAGINATOR_PER_PAGE),
            'user' => $admin
        ]));
    }

    #[Route('/user/{id}', name: 'user')]
    public function post(Request $request, Admin $admin, PostRepository $postRepository): Response
    {
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAdmin($admin);

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            return $this->redirectToRoute('user', ['id' => $admin->getId()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $postRepository->getPostPaginator($this->getUser()->getId(), $offset);

        return new Response($this->twig->render('post/user.html.twig', [
            'admin' => $admin,
            'last_posts' => $postRepository->findLast(),
            'post_form' => $form->createView(),
            'my_posts' => $paginator,
            'previous' => $offset - PostRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + PostRepository::PAGINATOR_PER_PAGE)
        ]));

    }

        #[Route('/delete/{slug}', name: 'delete')]
    public function deletePost($slug)
    {


        $post =  $this->getDoctrine()->getRepository(Post::class)->findOneBy(['slug'=>$slug]);
        $admin = $this->getUser();

        if ($post) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('user', ['id' => $admin->getId()] );

    }

    #[Route('/update/{slug}', name: 'update')]
    public function update(Post $post, Request $request)
    {
        $admin = $this->getUser();

        $up_form = $this->createForm(PostFormType::class, $post);
        $up_form->handleRequest($request);

        if ($up_form->isSubmitted() && $up_form->isValid()) {

            $this->entityManager->flush();

            return $this->redirectToRoute('user', ['id' => $admin->getId()] );
        }

        return $this->render('post/update.html.twig', [
            'up_form' => $up_form->createView()
        ]);
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
            'last_posts' => $postRepository->findLast(),
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView()
        ]));
    }

    #[Route('/search/posts', name: 'search')]
    public function searchPost(Request $request, PostRepository $postRepository)
    {
        $search = $request->request->get('search');
        $data = $postRepository->search($search);

        return new Response($this->twig->render('post/search.html.twig', [
            'search_posts' => $data,
            'last_posts' => $postRepository->findLast()
        ]));
    }

}
