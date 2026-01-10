<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Simple exemple of repository.
 */
final class UserRepository extends EntityRepository
{
    /**
     * Get all the post, but retreive only the id and title
     * Is not optimized, but it show how to get all result if needed.
     *
     * @return array<string, array<int, array<string, string|int|null>>> Returns an array containing all posts
     */
    public function adminExport(): array
    {
        $result = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'title' => 'User',
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
            ['field' => 'username', 'title' => 'Username'],
            ['field' => 'firstname', 'title' => 'Firstname'],
            ['field' => 'lastname', 'title' => 'Lastname'],
            ['field' => 'email', 'title' => 'EMail'],
        ];
    }

    /**
     * @param array<\App\Entity\User> $result
     *
     * @return array<int, array<string, string|int|null>>
     */
    private function formatData(array $result): array
    {
        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'id' => $row->getId(),
                'username' => $row->getUsername(),
                'firstname' => $row->getFirstName(),
                'lastname' => $row->getLastName(),
                'email' => $row->getEmail(),
            ];
        }

        return $data;
    }
}
