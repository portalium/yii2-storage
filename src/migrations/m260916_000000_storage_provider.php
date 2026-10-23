<?php

use portalium\db\Migration;
use portalium\storage\Module;
use portalium\workspace\Module as WorkspaceModule;

class m260916_000000_storage_provider extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $table = '{{%' . Module::$tablePrefix . 'provider}}';
        if ($this->db->getTableSchema($table, true) !== null) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($table, [
            'id_provider' => $this->primaryKey(),
            'name' => $this->string(64)->notNull(),
            'type' => $this->string(32)->notNull()->comment('local, s3, gdrive, dropbox'),
            'capability' => $this->string(16)->notNull()->defaultValue('storage')->comment('storage, source'),
            'config' => $this->text()->comment('Encrypted credentials'),
            'id_user' => $this->integer()->null()->comment('Creator, for audit only'),
            'id_workspace' => $this->integer()->null()->comment('NULL means system-wide'),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1)->comment('1 active, 0 passive'),
            'is_default' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'date_create' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'date_update' => $this->timestamp()->null()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex('idx_storage_provider_workspace', $table, 'id_workspace');
        $this->createIndex('idx_storage_provider_capability', $table, ['capability', 'status']);

        $this->addForeignKey(
            'fk_storage_provider_workspace',
            $table,
            'id_workspace',
            '{{%' . WorkspaceModule::$tablePrefix . 'workspace}}',
            'id_workspace',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_storage_provider_user',
            $table,
            'id_user',
            '{{%user_user}}',
            'id_user',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $table = '{{%' . Module::$tablePrefix . 'provider}}';

        $this->dropForeignKey('fk_storage_provider_user', $table);
        $this->dropForeignKey('fk_storage_provider_workspace', $table);
        $this->dropTable($table);
    }
}
