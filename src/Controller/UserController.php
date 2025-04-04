<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller managing user.
 */
class UserController extends AbstractController
{

    /**
     * @var TranslatorInterface The used translator interface
     */
    private TranslatorInterface $translator;

    /**
     * Generate controller.
     *
     * @param TranslatorInterface $translator Used translator.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
    ): Response {
        // Get request payload
        $json = $request->getContent();
        $data = json_decode($json, true);

        // Check that email is not already used
        $existing = $em->getRepository(User::class)->findOneByEmail(trim($data['email']));
        // If already used, throw 400 error
        if ($existing != null) {
            return $this->json([
                'error' => $this->translator->trans(
                    'user.email_already_used',
                    [
                        'email' => $data['email']
                    ],
                    'errors'
                )
            ], Response::HTTP_BAD_REQUEST);
        }

        // Create user accordingly
        $user = new User()
            ->setEmail($data['email'])
            ->setFirstname($data['firstname'])
            ->setUsername($data['username'])
            ->setRoles(['ROLE_USER']);
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
        return $this->json($user, Response::HTTP_CREATED, [], [
            'groups' => ['user.index']
        ]);
    }

    /**
     * Get logged in user info.
     *
     * @param Request $request Client request.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/me',
        name: 'user_info',
        requirements: [
            '_locale' => '%supported_locales%',
        ],
        methods: ['GET']
    )]
    public function getUserInfo(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        return $this->json($this->getUser(), Response::HTTP_OK, [], [
            'groups' => ['user.index']
        ]);
    }
}