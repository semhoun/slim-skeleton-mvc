<?php
namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 *  Post
 *
 * @ORM\Table(name="post")
 * @ORM\Entity
 */
class Post
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
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=true, options={"default"="null","fixed"=true})
     */
    private $title = 'null';

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=200, nullable=false, options={"fixed"=true})
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

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
    public function getTitle() : ?string
    {
      return $this->title;
    }
    public function setTitle(?string $val)
    {
      $this->title = $val;
    }
    public function getSlug() : string
    {
      return $this->slug;
    }
    public function setSlug(string $val)
    {
      $this->slug = $val;
    }
    public function getContent() : string
    {
      return $this->content;
    }
    public function setContent(string $val)
    {
      $this->content = $val;
    }
}
