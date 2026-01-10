<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'post')]
#[ORM\Entity(repositoryClass: PostRepository::class)]
final class Post implements \JsonSerializable
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    public function __construct(
        #[ORM\Column(name: 'content', type: 'text', nullable: false)]
        private string $content,
        #[ORM\Column(name: 'title', type: 'string', length: 100, nullable: true)]
        private ?string $title = null
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function updateTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function updateContent(string $content): void
    {
        $this->content = $content;
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
