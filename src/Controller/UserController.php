<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route(
        '/{_locale}/account',
        name: 'user_create',
        requirements: [
            '_locale' => '%supported_locales%',
        ],
        methods: ['POST']
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ) : Response
    {
        $json = $request->getContent();
        $data = json_decode($json, true);

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstname']);
        $user->setUsername($data['username']);
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json($user, 200, [], [
            'groups' => ['user.index']
        ]);
    }
}