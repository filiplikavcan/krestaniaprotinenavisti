<?php

namespace App\Model;

use App\Entity\Newsletter as NewsletterEntity;
use DateTimeImmutable;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Types;

class Newsletter extends AbstractModel
{
    /**
     * @param int $newsletterId
     * @return NewsletterEntity|null
     * @throws DBALException
     */
    public function findOneById(int $newsletterId): ?NewsletterEntity
    {
        $rawData = $this->query('
            SELECT
                id,
                name,
                created_at
            FROM
                newsletter
            WHERE
                id = :id
            LIMIT 1', [
            'id' => [$newsletterId, Types::INTEGER]
        ])->fetch();

        return false === $rawData ? null : $this->entity($rawData);
    }

    /**
     * @param array $rawData
     * @return NewsletterEntity
     */
    private function entity(array $rawData): NewsletterEntity
    {
        $newsletter = new NewsletterEntity();

        $newsletter->setId((int)$rawData['id']);
        $newsletter->setName($rawData['name']);
        $newsletter->setCreatedAt(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['created_at']));

        return $newsletter;
    }
}