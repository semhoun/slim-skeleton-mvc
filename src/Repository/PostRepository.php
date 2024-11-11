<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Simple exemple of repository
 */
final class PostRepository extends EntityRepository
{
    /**
     * Get all the post, but retreive only the id and title
     * Is not optimized, but it show how to get all result if needed
     *
     * @return array<mixed> Returns an array containing all posts
     */
    public function adminExport(): array
    {
        $result = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [
            'title' => 'Blog Posts',
            'columns' => [
                [
                    'field' => 'id',
                    'title' => 'Id',
                ],
                [
                    'field' => 'title',
                    'title' => 'Title',
                ],
                [
                    'field' => 'action',
                    'title' => '',
                ],
            ],
            'data' => [],
        ];
        foreach ($result as $row) {
            $data['data'][] = [
                'id' => $row->getId(),
                'title' => $row->getTitle(),
                'action' => '<a href="/blog/' . $row->getId() . '">View</a>',
            ];
        }

        return $data;
    }
}
