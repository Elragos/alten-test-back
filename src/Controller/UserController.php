<?php

namespace App\Controller;

use App\Service\UserService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller managing user.
 */
class UserController extends AbstractController
{

    /**
     * @var UserService Used user service.
     */
    private UserService $userService;

    /**
     * @var TranslatorInterface The used translator interface
     */
    private TranslatorInterface $translator;

    /**
     * Generate controller.
     *
     * @param TranslatorInterface $translator Used translator.
     */
    public function __construct(
        UserService $userService,
        TranslatorInterface $translator
    ) {
        $this->userService = $userService;
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
    ): Response {
        // Get request payload
        $json = $request->getContent();
        $data = json_decode($json, true);
        // Register User
        try {
            $user = $this->userService->register($data);
        }
        // If user email alredy used
        catch (InvalidArgumentException) {
            // Throw 400 error
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

        // Send updated user info
        return $this->json(
            $user,
            Response::HTTP_CREATED,
            [],
            [
                'groups' => ['user.index']
            ]
        );
    }

    /**
     * Get logged in user info.
     *
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
    public function getUserInfo(): Response
    {
        return $this->json(
            $$this->getUser(),
            Response::HTTP_OK,
            [],
            [
                'groups' => ['user.index']
            ]
        );
    }
}