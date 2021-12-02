<?php

namespace App\Controller;


use App\Entity\Admin;
use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Twig\Environment;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\AdminFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{

    private $twig;

    private $em;


    public function __construct(EntityManagerInterface $em, Environment $twig)
    {
        $this->em = $em;
        $this->twig = $twig;
    }


    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();



        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }


    #[Route('/registration', name: 'reg')]
    public function registration(Request $request, UserPasswordHasherInterface $passwordHasher)
    {

        $admin = new Admin();


        $form = $this->createForm(AdminFormType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plaintextPassword = $admin->getPassword();
            $hashed = $passwordHasher->hashPassword($admin, $plaintextPassword);
            $admin->setPassword($hashed);


            $admin->setRoles(["ROLE_USER"]);
            $this->em->persist($admin);
            $this->em->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/registration.html.twig', [
            'admin_form' => $form->createView()
        ]);

    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
