<?php

namespace App\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;

class AbstractModel
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @return Statement
     * @throws DBALException
     */
    protected function query(string $sql, array $parameters = []): Statement
    {
        $queryParameters = [];

        foreach ($parameters as $parameterName => $parameter)
        {
            $queryParameters[$parameterName] = $parameter;
        }

        $statement = $this->connection->prepare($sql);

        foreach ($parameters as $name => $value)
        {
            $statement->bindValue($name, $value[0], $value[1]);
        }

        $statement->execute();

        return $statement;
    }
}