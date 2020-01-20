<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200120190037 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('signature');
        $table->addColumn('id', 'integer', ['autoincrement' => true,]);
        $table->addColumn('first_name', 'string');
        $table->addColumn('last_name', 'string');
        $table->addColumn('email', 'string');
        $table->addColumn('city', 'string');
        $table->addColumn('occupation', 'string', ['notnull' => false]);
        $table->addColumn('allow_display', 'boolean');
        $table->addColumn('agree_with_support_statement', 'boolean');
        $table->addColumn('agree_with_contact_later', 'boolean');
        $table->addColumn('hash', 'string', ['length' => 32]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('verified_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['hash']);
        $table->addIndex(['verified_at']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('signature');
    }
}
