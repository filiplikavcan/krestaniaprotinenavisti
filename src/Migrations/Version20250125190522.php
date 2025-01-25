<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250125190522 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $schema->getTable('signature')->addColumn('display', 'string', ['length' => 255]);
        $this->connection->executeStatement('UPDATE signature SET display = IF(allow_display = 1,\'full\', \'anonymous\')');
    }

    public function down(Schema $schema) : void
    {
        $schema->getTable('signature')->dropColumn('display');
    }
}
