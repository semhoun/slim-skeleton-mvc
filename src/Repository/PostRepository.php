<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Simple exemple of repository.
 */
final class PostRepository extends EntityRepository
{
    /**
     * Get all the post, but retreive only the id and title
     * Is not optimized, but it show how to get all result if needed.
     *
     * @return array<string, array<int, array<string, string|int>>> Returns an array containing all posts
     */
    public function adminExport(): array
    {
        $result = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'title' => 'Blog Posts',
            'columns' => $this->getColumns(),
            'data' => $this->formatData($result),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getColumns(): array
    {
        return [
            ['field' => 'id', 'title' => 'Id'],
            ['field' => 'title', 'title' => 'Title'],
            ['field' => 'action', 'title' => ''],
        ];
    }

    /**
     * @param array<\App\Entity\Post> $result
     *
     * @return array<int, array<string, string|int>>
     */
    private function formatData(array $result): array
    {
        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'id' => $row->getId(),
                'title' => $row->getTitle(),
                'action' => '<a href="/blog/' . $row->getId() . '">View</a>',
            ];
        }

        return $data;
    }
}
