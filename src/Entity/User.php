<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Entity representing an app user.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var int|null User ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string|null User email, unique in DB.
     */
    #[ORM\Column(length: 180)]
    #[Groups(['user.index'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles.
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password.
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var string The user name.
     */
    #[ORM\Column(length: 255)]
    #[Groups(['user.index'])]
    private ?string $username = null;

    /**
     * @var string The user first name.
     */
    #[ORM\Column(length: 255)]
    #[Groups(['user.index'])]
    private ?string $firstname = null;

    /**
     * @var string The currently used API token (not used ATM)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiToken = null;

    /**
     * @var Wishlist|null The user wishlist
     */
    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Wishlist $wishlist = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): static
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getWishlist(): ?Wishlist
    {
        return $this->wishlist;
    }

    public function setWishlist(?Wishlist $wishlist): static
    {
        // unset the owning side of the relation if necessary
        if ($wishlist === null && $this->wishlist !== null) {
            $this->wishlist->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($wishlist !== null && $wishlist->getUser() !== $this) {
            $wishlist->setUser($this);
        }

        $this->wishlist = $wishlist;

        return $this;
    }
}
