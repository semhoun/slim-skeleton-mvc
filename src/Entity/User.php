<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity
 */
class User
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=30, nullable=false, options={"fixed"=true})
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=60, nullable=false, options={"fixed"=true})
     */
    private $password;

    /**
     * @var string|null
     *
     * @ORM\Column(name="first_name", type="string", length=50, nullable=true, options={"fixed"=true})
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_name", type="string", length=50, nullable=true, options={"fixed"=true})
     */
    private $lastName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=50, nullable=true, options={"fixed"=true})
     */
    private $email;

    /********************
     * Getter and Setter
     ********************/
    public function getId() : int
    {
      return $this->id;
    }
    public function setId(int $val)
    {
      $this->id = $val;
    }
    public function getUsername() : string
    {
      return $this->username;
    }
    public function setUsername(string $val)
    {
      $this->username = $val;
    }
    public function getPassword() : string
    {
      return $this->password;
    }
    public function setPassword(string $val)
    {
      $this->password = $val;
    }
    public function getFirstName() : ?string
    {
      return $this->firstName;
    }
    public function setFirstName(?string $val)
    {
      $this->firstName = $val;
    }
    public function getLastName() : ?string
    {
      return $this->lastName;
    }
    public function setLastName(?string $val)
    {
      $this->lastName = $val;
    }
    public function getEmail() : ?string
    {
      return $this->email;
    }
    public function setEmail(?string $val)
    {
      $this->email = $val;
    }
}
