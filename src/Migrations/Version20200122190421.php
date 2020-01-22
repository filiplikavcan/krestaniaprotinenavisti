<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200122190421 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('signature');

        $table->addColumn('notification_count', 'integer', ['notnull' => false]);
        $table->addColumn('last_notified_at', 'datetime', ['notnull' => false]);
        $table->addIndex(['allow_display'], 'allow_display');
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('signature');

        $table->dropColumn('notification_count');
        $table->dropColumn('last_notified_at');
        $table->dropIndex('allow_display');
    }
}
