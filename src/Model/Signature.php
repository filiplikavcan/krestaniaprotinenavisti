<?php

namespace App\Model;

use App\Entity\Signature as SignatureEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Types;
use Exception;

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
            ORDER BY verified_at DESC 
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
            ORDER BY verified_at DESC');

        foreach ($query->fetchAll() as $rawData)
        {
            yield $this->entity($rawData);
        }
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
        $signature->setHash($rawData['email']);
        $signature->setCreatedAt(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['created_at']));
        $signature->setVerifiedAt(null === $rawData['verified_at'] ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawData['verified_at']));

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