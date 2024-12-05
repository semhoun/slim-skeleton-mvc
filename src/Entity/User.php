<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'username', type: 'string', length: 30, nullable: false)]
    private string $username;

    #[ORM\Column(name: 'password', type: 'string', length: 60, nullable: false)]
    private string $password;

    #[ORM\Column(name: 'first_name', type: 'string', length: 50, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 50, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'email', type: 'string', length: 50, nullable: true)]
    private ?string $email = null;

    /*
     * Getter and Setter
     */
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $val): void
    {
        $this->id = $val;
    }
    public function getUsername(): string
    {
        return $this->username;
    }
    public function setUsername(string $val): void
    {
        $this->username = $val;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $val): void
    {
        $this->password = $val;
    }
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    public function setFirstName(?string $val): void
    {
        $this->firstName = $val;
    }
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    public function setLastName(?string $val): void
    {
        $this->lastName = $val;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(?string $val): void
    {
        $this->email = $val;
    }
}
