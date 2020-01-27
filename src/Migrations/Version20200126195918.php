<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200126195918 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('newsletter');
        $table->addColumn('id', 'integer', ['autoincrement' => true,]);
        $table->addColumn('name', 'string');
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(['id']);

        $table2 = $schema->createTable('signature_newsletter');
        $table2->addColumn('signature_id', 'integer');
        $table2->addColumn('newsletter_id', 'integer');
        $table2->addColumn('sent_at', 'datetime');
        $table2->addColumn('unsubscribed_at', 'datetime', ['notnull' => false]);
        $table2->setPrimaryKey(['signature_id', 'newsletter_id']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('newsletter');
        $schema->dropTable('signature_newsletter');
    }
}
