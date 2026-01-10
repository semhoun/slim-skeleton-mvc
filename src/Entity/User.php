<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
final class User
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'first_name', type: 'string', length: 50, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 50, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'email', type: 'string', length: 50, nullable: true)]
    private ?string $email = null;

    public function __construct(
        #[ORM\Column(name: 'username', type: 'string', length: 30, nullable: false)]
        private string $username,
        #[ORM\Column(name: 'password', type: 'string', length: 60, nullable: false)]
        private string $password
    )
    {
    }

    /*
     * Getters and behavior methods
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function changeUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function changePassword(string $password): void
    {
        $this->password = $password;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function updateFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function updateLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function updateEmail(?string $email): void
    {
        $this->email = $email;
    }
}
