<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class handling all user manipulations.
 */
class UserService
{

    /**
     * @var UserRepository Used user repository.
     */
    private UserRepository $userRepository;

    private UserPasswordHasherInterface $passwordHasher;

    /**
     * Initiate service.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(array $data) : User
    {
        // Check that email is not already used
        $existing = $this->userRepository->findOneByEmail(trim($data['email']));
        // If already used
        if ($existing != null) {
           throw new InvalidArgumentException("email");
        }

        // Create user accordingly
        $user = new User()
            ->setEmail($data['email'])
            ->setFirstname($data['firstname'])
            ->setUsername($data['username'])
            ->setRoles(['ROLE_USER']);
        // Encrypt password in DB
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $data['password']
        );
        $user->setPassword($hashedPassword);
        // Save created user
        $this->userRepository->save($user);
        // Return created user
        return $user;
    }
}