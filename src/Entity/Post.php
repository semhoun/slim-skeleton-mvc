<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'post')]
#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post implements \JsonSerializable
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'title', type: 'string', length: 100, nullable: true)]
    private ?string $title = 'null';

    #[ORM\Column(name: 'content', type: 'text', nullable: false)]
    private string $content;

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
    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(?string $val): void
    {
        $this->title = $val;
    }
    public function getContent(): string
    {
        return $this->content;
    }
    public function setContent(string $val): void
    {
        $this->content = $val;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
