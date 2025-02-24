<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller managing user.
 */
class UserController extends AbstractController
{

    /**
     * Create new user.
     *  Expected payload :
     * {
     *  "username": "User name",
     *  "firstname": "User first name",
     *  "email": "user email",
     *  "password": "user password"
     * }
     *
     * @param Request $request Client request.
     * @param UserPasswordHasherInterface $passwordHasher Used password hasher.
     * @param EntityManagerInterface $em Used entity manager.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
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
        // Get request payload
        $json = $request->getContent();
        $data = json_decode($json, true);

        // Create user accordingly
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstname']);
        $user->setUsername($data['username']);
        // Encrypt password in DB
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );
        $user->setPassword($hashedPassword);
        // Save created user
        $em->persist($user);
        $em->flush();
        // Send updated user info
        return $this->json($user, 201, [], [
            'groups' => ['user.index']
        ]);
    }
}