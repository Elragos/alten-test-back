<?php

namespace App\DataFixture;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
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

    /**
     * @var string Application config path.
     */
    private string $configPath;

    public function __construct(
        UserPasswordHasherInterface $passwordEncoder,
        #[Autowire('%kernel.project_dir%/config')] string $configPath
    )
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->configPath = $configPath;
    }

    /**
     * Create initial data from JSON config file.
     *
     * @param ObjectManager $manager Used DB manager.
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // Load initial data
        $initialDataPath = $this->configPath . '/initialData.json';
        if (file_exists($initialDataPath))
        {
            $initialDataContent = file_get_contents($initialDataPath);
            // Parse JSON
            $initialData = json_decode($initialDataContent, true);

            // For all users in initial data
            foreach ($initialData["users"] as $userData) {
                // Create it
                $user = new User();
                $user->setUsername($userData["username"])
                    ->setFirstname($userData["firstname"])
                    ->setEmail($userData["email"])
                    ->setPassword(
                        $this->passwordEncoder->hashPassword($user, $userData["password"])
                    )
                    ->setRoles([$userData["role"]]);

                $this->createAccount($user, $manager);
            }
            // For all products in initial data
            foreach ($initialData["products"] as $productData) {
                // Create it
                $product = new Product();
                $product->setCode($productData["code"])
                    ->setName($productData["name"])
                    ->setDescription($productData["description"])
                    ->setImage($productData["image"])
                    ->setCategory($productData["category"])
                    ->setPrice($productData["price"])
                    ->setQuantity($productData["quantity"])
                    ->setInternalReference($productData["internalReference"])
                    ->setShellId($productData["shellId"])
                    ->setInventoryStatus($productData["inventoryStatus"])
                    ->setRating($productData["rating"])
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());

                $this->createProduct($product, $manager);
            }
        }
        else
        {
            throw new FileNotFoundException("Initial data file \"$initialDataPath\" does not exist.");
        }

        // Create default admin account
        $this->createDefaultAdmin($manager);
    }

    /**
     * Create user in DB if not exists.
     *
     * @param User $toCreate User to create.
     * @param ObjectManager $manager Used DB manager
     * @return void
     */
    private function createAccount(User $toCreate, ObjectManager $manager) : void
    {
        // Check if account exists
        $exists = $manager->getRepository(User::class)->findOneBy([
            "email" => $toCreate->getEmail()
        ]);

        if ($exists == null) {
            $manager->persist($toCreate);
            $manager->flush();
        }
    }

    /**
     * Create default admin account with password 123456 (if not already registered).
     *
     * @param ObjectManager $manager Used DB manager.
     * @return void
     */
    private function createDefaultAdmin(ObjectManager $manager) : void
    {
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setFirstname('admin');
        $admin->setEmail('admin@admin.com');
        $admin->setPassword(
            $this->passwordEncoder->hashPassword($admin, '123456')
        );
        $admin->setRoles(['ROLE_ADMIN']);
        $this->createAccount($admin, $manager);
    }

    private function createProduct(Product $toCreate, ObjectManager $manager) : void
    {
        // Check if account exists
        $exists = $manager->getRepository(Product::class)->findOneBy([
            "code" => $toCreate->getCode()
        ]);

        if ($exists == null) {
            $manager->persist($toCreate);
            $manager->flush();
        }
    }
}