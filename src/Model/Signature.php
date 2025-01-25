<?php

namespace App\Model;

use App\Entity\Signature as SignatureEntity;
use DateTimeImmutable;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Types;
use Exception;
use Generator;
use function Symfony\Component\String\u;

class Signature extends AbstractModel
{
    public function signaturesCount(string $petition): int
    {
        return $this->query('
            SELECT
                COUNT(*) AS signatures_count
            FROM
                signature
            WHERE
                petition = :petition
                    AND
                verified_at IS NOT NULL', [
            'petition' => [$petition, Types::STRING]
        ])->fetch()['signatures_count'];
    }

    public function visibleSignaturesCount(string $petition): int
    {
        return $this->query('
            SELECT
                COUNT(*) AS signatures_count
            FROM
                signature
            WHERE
                petition = :petition
                    AND
                verified_at IS NOT NULL
                    AND
                allow_display = 1', [
            'petition' => [$petition, Types::STRING]
        ])->fetch()['signatures_count'];
    }

    /**
     * @param SignatureEntity $signature
     * @return bool
     */
    public function markAsNotified(SignatureEntity $signature): bool
    {
        $this->query('
            UPDATE
                signature
            SET
                last_notified_at = NOW(), 
                notification_count = :notification_count
            WHERE
                id = :id', [
            'id' => [$signature->getId(), Types::INTEGER],
            'notification_count' => [$signature->getNotificationCount() + 1, Types::INTEGER],
        ])->execute();

        return true;
    }

    public function markNewsletterSent(int $newsletterId, int $signatureId): bool
    {
        try {
            $this->query('
                INSERT INTO 
                    signature_newsletter 
                (
                    signature_id, 
                    newsletter_id, 
                    sent_at
                )
                VALUES
                (
                 :signature_id,
                 :newsletter_id,
                 NOW()
                )', [
                'signature_id' => [$signatureId, Types::INTEGER],
                'newsletter_id' => [$newsletterId, Types::INTEGER],
            ]);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    public function newsletterableSignatures(int $newsletterId, int $limit): Generator
    {
        $query = $this->query("
            SELECT
                {$this->fields()}
            FROM
                signature AS s
            LEFT JOIN
                signature_newsletter sn 
            ON
                s.id = sn.signature_id
                    AND
                sn.newsletter_id = :newsletter_id
            WHERE
                s.verified_at IS NOT NULL
                    AND
                sn.signature_id IS NULL
                    AND
                s.agree_with_contact_later = 1
                    AND
                s.created_at < NOW() - INTERVAL 1 HOUR
            ORDER BY 
                created_at
            LIMIT 
                :limit", [
            'newsletter_id' => [$newsletterId, Types::INTEGER],
            'limit' => [$limit, Types::INTEGER],
        ]);

        while ($rawData = $query->fetch()) {
            yield $this->entity($rawData);
        }
    }

    private function fields()
    {
        return '
            s.id,
            s.first_name,
            s.last_name,
            s.email,
            s.city,
            s.occupation,
            s.allow_display,
            s.agree_with_support_statement,
            s.agree_with_contact_later,
            s.hash,
            s.created_at,
            s.verified_at,
            s.notification_count,
            s.last_notified_at,
            s.is_multiple_email_ok';
    }

    private function entity(array $rawData): SignatureEntity
    {
        $signature = new SignatureEntity();

        $signature->setId($rawData['id']);
        $signature->setFirstName($rawData['first_name']);
        $signature->setLastName($rawData['last_name']);
        $signature->setCity($rawData['city']);
        $signature->setEmail($rawData['email']);
        $signature->setOccupation($rawData['occupation']);
        $signature->setAllowDisplay((boolean)$rawData['allow_display']);
        $signature->setAgreeWithSupportstatement((boolean)$rawData['agree_with_support_statement']);
        $signature->setAgreeWithContactLater((boolean)$rawData['agree_with_contact_later']);
        $signature->setHash($rawData['hash']);
        $signature->setCreatedAt(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['created_at']));
        $signature->setVerifiedAt(null === $rawData['verified_at'] ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['verified_at']));
        $signature->setLastNotifiedAt(null === $rawData['last_notified_at'] ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['last_notified_at']));
        $signature->setNotificationCount(null === $rawData['notification_count'] ? 0 : (int)$rawData['notification_count']);

        return $signature;
    }

    public function notifiableSignatures(int $limit): Generator
    {
        $query = $this->query('
            SELECT
                *
            FROM
                signature
            WHERE
                verified_at IS NULL
                    AND
                (
                    notification_count IS NULL 
                        OR
                    notification_count <= 3
                )
                    AND
                IFNULL(last_notified_at, created_at) < NOW() - INTERVAL (IFNULL(notification_count, 0) * 2 + 1) DAY
            ORDER BY 
                IFNULL(last_notified_at, created_at) ASC 
            LIMIT 
                ' . $limit);

        foreach ($query->fetchAll() as $rawData) {
            yield $this->entity($rawData);
        }
    }

    public function lastVisibleSignatures(int $limit, string $petition)
    {
        $query = $this->query('
            SELECT
                *
            FROM
                signature
            WHERE
                verified_at IS NOT NULL 
                    AND
                allow_display = 1
                    AND
                petition = :petition
            ORDER BY
                verified_at DESC 
            LIMIT ' . $limit, [
            'petition' => [$petition, Types::STRING]
        ]);

        foreach ($query->fetchAll() as $rawData) {
            yield $this->entity($rawData);
        }
    }

    public function allVisibleSignatures(string $petition)
    {
        $query = $this->query('
            SELECT
                *
            FROM
                signature
            WHERE
                verified_at IS NOT NULL 
                    AND
                allow_display = 1
                    AND
                petition = :petition
            ORDER BY
                verified_at DESC', [
            'petition' => [$petition, Types::STRING]
        ]);

        while ($rawData = $query->fetch()) {
            yield $this->entity($rawData);
        }
    }

    public function top100OccupationWords()
    {
        $synonyms = [
        ];

        $statement = $this->query('
            SELECT
                occupation
            FROM
                signature
            WHERE
                verified_at IS NOT NULL 
                    AND
                allow_display = 1');

        $result = [];
        $prefixed = [];

        while ($row = $statement->fetch()) {
            if (!empty($row['occupation'])) {
                foreach (preg_split('/[ |\/,\.\-]/', $row['occupation']) as $word) {
                    $baseWord = u($word)->ascii()->trim()->trim('-')->lower();
                    $unifiedWord = u($word)->trim()->trim('-')->lower()->toString();

                    $keyWord = $baseWord->slice(0, 6)->toString();
                    $normalizedWord = $baseWord->toString();

                    if (isset($synonyms[$normalizedWord])) {
                        $keyWord = u($synonyms[$keyWord])->slice(0, 6)->toString();
                    }

                    if (strlen($normalizedWord) > 3) {
                        if (!isset($result[$unifiedWord])) {
                            $result[$unifiedWord] = 0;
                        }

                        $result[$unifiedWord]++;

                        if (!isset($prefixed[$keyWord])) {
                            $prefixed[$keyWord] = [];
                        }

                        $prefixed[$keyWord][$unifiedWord] = $unifiedWord;
                    }
                }
            }
        }

        arsort($result);

        $slice = array_slice($result, 0, 150);

        foreach ($slice as $word => $count) {
            $keyWord = u($word)->ascii()->trim()->trim('-')->lower()->slice(0, 6)->toString();

            foreach ($prefixed[$keyWord] as $otherWord) {
                if ($otherWord !== $word) {
                    $slice[$word] += $result[$otherWord];
                }
            }
        }

        return $slice;
    }

    public function overallStats()
    {
        return $this->query('
            SELECT
                SUM(IF(verified_at IS NULL, 1, 0)) AS unverified,
                SUM(IF(verified_at IS NOT NULL, 1, 0)) AS verified,
                SUM(IF(verified_at IS NULL, 1, 0)) / COUNT(*) * 100 AS unverified_rate
            FROM
                signature
            LIMIT 1')->fetch();
    }

    public function hourlyStats()
    {
        return $this->query('
            SELECT
                DATE_FORMAT(created_at, \'%e.%c. %H\') AS age,
                DATE_FORMAT(created_at, \'%H\') AS `hour`,
                SUM(IF(verified_at IS NULL, 1, 0)) AS unverified,
                SUM(IF(verified_at IS NOT NULL, 1, 0)) AS verified,
                SUM(IF(verified_at IS NULL, 1, 0)) / COUNT(*) * 100 AS unverified_rate
            FROM
                signature
            GROUP BY age
            ORDER BY age DESC')->fetchAll();
    }

    public function create()
    {
        return new SignatureEntity();
    }

    /**
     * @param SignatureEntity $signature
     * @return SignatureEntity
     * @throws DBALException
     */
    public function insert(SignatureEntity $signature): SignatureEntity
    {
        try {
            $signature->setHash(md5(random_bytes(32)));
        } catch (Exception $e) {
            $signature->setHash(md5(rand(1, 10000000) . $signature->getEmail() . rand(1, 10000000)));
        }

        $this->connection->insert('signature', [
            'first_name' => $signature->getFirstName(),
            'last_name' => $signature->getLastName(),
            'email' => $signature->getEmail(),
            'city' => $signature->getCity(),
            'occupation' => $signature->getOccupation(),
            'allow_display' => $signature->getAllowDisplay(),
            'agree_with_support_statement' => $signature->getAgreeWithSupportstatement(),
            'agree_with_contact_later' => $signature->getAgreeWithContactLater(),
            'created_at' => new DateTimeImmutable(),
            'hash' => $signature->getHash(),
            'petition' => $signature->getPetition(),
        ], [
            Types::STRING,
            Types::STRING,
            Types::STRING,
            Types::STRING,
            Types::STRING,
            Types::BOOLEAN,
            Types::BOOLEAN,
            Types::BOOLEAN,
            Types::DATETIME_IMMUTABLE,
            Types::STRING,
            Types::STRING,
        ]);

        return $signature;
    }

    /**
     * @param string $hash
     * @return SignatureEntity|null
     * @throws DBALException
     */
    public function verify(string $hash): ?SignatureEntity
    {
        $rawData = $this->query('SELECT * FROM signature WHERE hash = :hash', [
            'hash' => [$hash, Types::STRING]
        ])->fetch();

        if (false === $rawData) {
            return null;
        }

        $signature = $this->entity($rawData);

        if (null === $signature->getVerifiedAt()) {
            $signature->setVerifiedAt(new DateTimeImmutable());

            $this->connection->update('signature', [
                'verified_at' => $signature->getVerifiedAt(),
            ], [
                'id' => $signature->getId()
            ], [
                Types::DATETIME_IMMUTABLE
            ]);
        }

        return $signature;
    }

    /**
     * @param string $hash
     * @return SignatureEntity|null
     * @throws DBALException
     */
    public function findOneByHash(string $hash): ?SignatureEntity
    {
        $rawData = $this->query("
            SELECT
                {$this->fields()}
            FROM
                signature AS s
            WHERE
                s.hash = :hash
            LIMIT 1
            ", [
            'hash' => [$hash, Types::STRING],
        ])->fetch();

        return false === $rawData ? null : $this->entity($rawData);
    }

    /**
     * @param int $signatureId
     * @param int $newsletterId
     * @throws DBALException
     */
    public function unsubscribe(int $signatureId, int $newsletterId): void
    {
        $this->query('
            UPDATE 
                signature 
            SET 
                agree_with_contact_later = 0 
            WHERE 
                id = :id', [
            'id' => [$signatureId, Types::INTEGER]
        ])->execute();

        $this->query('
            UPDATE 
                signature_newsletter 
            SET 
                unsubscribed_at = NOW() 
            WHERE 
                signature_id = :signature_id
                    AND
                newsletter_id = :newsletter_id', [
            'signature_id' => [$signatureId, Types::INTEGER],
            'newsletter_id' => [$newsletterId, Types::INTEGER],
        ])->execute();
    }
}
