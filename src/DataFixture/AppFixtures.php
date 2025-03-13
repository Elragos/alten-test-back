<?php

namespace App\DataFixture;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Data initialization for application.
 */
class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordHasherInterface Used password encoder.
     */
    private UserPasswordHasherInterface $passwordEncoder;

    public function __construct(UserPasswordHasherInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Create initial data (admin user wish pwd 123456)
     * @param ObjectManager $manager Used DB manager.
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // Find if admin user already created
        $admin = $manager->getRepository(User::class)->findOneBy([
            "email" => "admin@admin.com"
        ]);

        // If not
        if ($admin == null) {
            // Create it
            $admin = new User();
            $admin->setUsername('admin');
            $admin->setFirstname('admin');
            $admin->setEmail('admin@admin.com');
            $admin->setPassword(
                $this->passwordEncoder->hashPassword($admin, '123456')
            );
            $admin->setRoles(['ROLE_ADMIN']);

            $manager->persist($admin);
            $manager->flush();
        }
    }
}