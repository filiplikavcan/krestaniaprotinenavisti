<?php

namespace App\Model;

use App\Entity\Signature as SignatureEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Types;
use Exception;
use Generator;
use function Symfony\Component\String\u;

class Signature
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function signaturesCount(): int
    {
        return $this->query('
            SELECT
                COUNT(*) AS signatures_count
            FROM
                signature
            WHERE
                verified_at IS NOT NULL')->fetch()['signatures_count'];
    }

    public function visibleSignaturesCount(): int
    {
        return $this->query('
            SELECT
                COUNT(*) AS signatures_count
            FROM
                signature
            WHERE
                verified_at IS NOT NULL
                    AND
                allow_display = 1')->fetch()['signatures_count'];
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
                    notification_count < 2
                )
                    AND
                IFNULL(last_notified_at, created_at) < NOW() - INTERVAL (IFNULL(notification_count, 0) * 3 + 1) DAY
            ORDER BY 
                IFNULL(last_notified_at, created_at) ASC 
            LIMIT 
                ' . $limit);

        foreach ($query->fetchAll() as $rawData)
        {
            yield $this->entity($rawData);
        }
    }

    public function lastVisibleSignatures()
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
            ORDER BY
                verified_at DESC 
            LIMIT 50');

        foreach ($query->fetchAll() as $rawData)
        {
            yield $this->entity($rawData);
        }
    }

    public function allVisibleSignatures()
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
            ORDER BY
                verified_at DESC');

        while ($rawData =$query->fetch())
        {
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

        while ($row = $statement->fetch())
        {
            if (!empty($row['occupation']))
            {
                foreach (preg_split('/[ |\/,\.\-]/', $row['occupation']) as $word)
                {
                    $baseWord = u($word)->ascii()->trim()->trim('-')->lower();
                    $unifiedWord = u($word)->trim()->trim('-')->lower()->toString();

                    $keyWord = $baseWord->slice(0, 6)->toString();
                    $normalizedWord = $baseWord->toString();

                    if (isset($synonyms[$normalizedWord]))
                    {
                        $keyWord = u($synonyms[$keyWord])->slice(0, 6)->toString();
                    }

                    if (strlen($normalizedWord) > 3)
                    {
                        if (!isset($result[$unifiedWord]))
                        {
                            $result[$unifiedWord] = 0;
                        }

                        $result[$unifiedWord]++;

                        if (!isset($prefixed[$keyWord]))
                        {
                            $prefixed[$keyWord] = [];
                        }

                        $prefixed[$keyWord][$unifiedWord] = $unifiedWord;
                    }
                }
            }
        }

        arsort($result);

        $slice = array_slice($result, 0, 150);

        foreach ($slice as $word => $count)
        {
            $keyWord = u($word)->ascii()->trim()->trim('-')->lower()->slice(0, 6)->toString();

            foreach ($prefixed[$keyWord] as $otherWord)
            {
                if ($otherWord !== $word)
                {
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

    private function entity(array $rawData): SignatureEntity
    {
        $signature = new SignatureEntity();

        $signature->setId($rawData['id']);
        $signature->setFirstName($rawData['first_name']);
        $signature->setLastName($rawData['last_name']);
        $signature->setCity($rawData['city']);
        $signature->setEmail($rawData['email']);
        $signature->setOccupation($rawData['occupation']);
        $signature->setAllowDisplay((boolean) $rawData['allow_display']);
        $signature->setAgreeWithSupportstatement((boolean) $rawData['agree_with_support_statement']);
        $signature->setAgreeWithContactLater((boolean) $rawData['agree_with_contact_later']);
        $signature->setHash($rawData['hash']);
        $signature->setCreatedAt(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['created_at']));
        $signature->setVerifiedAt(null === $rawData['verified_at'] ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['verified_at']));
        $signature->setLastNotifiedAt(null === $rawData['last_notified_at'] ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['last_notified_at']));
        $signature->setNotificationCount(null === $rawData['notification_count'] ? 0 : (int) $rawData['notification_count']);

        return $signature;
    }

    /**
     * @param SignatureEntity $signature
     * @return SignatureEntity
     * @throws DBALException
     */
    public function insert(SignatureEntity $signature): SignatureEntity
    {
        try
        {
            $signature->setHash(md5(random_bytes(32)));
        }
        catch (Exception $e)
        {
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

        if (false === $rawData)
        {
            return null;
        }

        $signature = $this->entity($rawData);

        if (null === $signature->getVerifiedAt())
        {
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

    private function query(string $sql, array $parameters = []): Statement
    {
        $queryParameters = [];

        foreach ($parameters as $parameterName => $parameter) {
            $queryParameters[$parameterName] = $parameter;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $statement = $this->connection->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue($name, $value[0], $value[1]);
        }

        $statement->execute();

        return $statement;
    }
}