<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221105182800 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('signature');
        $table->addColumn('petition', 'string', ['notnull' => true]);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('signature');
        $table->dropColumn('petition');
    }
}
