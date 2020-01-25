<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200125122111 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('signature');
        $table->addColumn('is_multiple_email_ok', 'boolean', ['notnull' => false]);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('signature');
        $table->dropColumn('is_multiple_email_ok');
    }
}
