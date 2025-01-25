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
    }

    public function down(Schema $schema) : void
    {
        $schema->getTable('signature')->dropColumn('display');
    }
}
