<?php

use portalium\db\Migration;
use portalium\storage\Module;
use portalium\user\Module as UserModule;
use portalium\workspace\Module as WorkspaceModule;

class m260720_135808_provider_table extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB';
        $table = '{{%' . Module::$tablePrefix . 'storage_provider}}';

        $this->createTable($table, [
            'id_provider'    => $this->primaryKey(11),
            'id_user'        => $this->integer(11)->notNull(),
            'id_workspace'   => $this->integer(11)->notNull(),
            'provider_type'  => $this->string(32)->notNull(),
            'provider_name'  => $this->string(128)->null(),
            'external_id'    => $this->string(128)->null(),
            'display_email'  => $this->string(256)->null(),
            'access_token'   => $this->text()->null(),
            'refresh_token'  => $this->text()->null(),
            'token_type'     => $this->string(32)->null(),
            'expires_at'     => $this->dateTime()->null(),
            'scope'          => $this->text()->null(),
            'status'         => $this->string(32)->notNull()->defaultValue('active'),
            'last_sync_at'   => $this->dateTime()->null(),
            'date_create'    => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'date_update'    => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage_provider-id_user}}',
            $table,
            'id_user',
            '{{%' . UserModule::$tablePrefix . 'user}}',
            'id_user',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage_provider-id_workspace}}',
            $table,
            'id_workspace',
            '{{%' . WorkspaceModule::$tablePrefix . 'workspace}}',
            'id_workspace',
            'CASCADE'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage_provider-id_user}}',
            $table,
            'id_user'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage_provider-id_workspace}}',
            $table,
            'id_workspace'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage_provider-external_id}}',
            $table,
            'external_id'
        );

        $this->createIndex(
            '{{%uniq-' . Module::$tablePrefix . 'storage_provider-workspace_type_user}}',
            $table,
            ['id_workspace', 'provider_type', 'id_user'],
            true
        );
    }

    public function safeDown()
    {
        $table = '{{%' . Module::$tablePrefix . 'storage_provider}}';

        $this->dropForeignKey('{{%fk-' . Module::$tablePrefix . 'storage_provider-id_user}}', $table);
        $this->dropForeignKey('{{%fk-' . Module::$tablePrefix . 'storage_provider-id_workspace}}', $table);

        $this->dropTable($table);
    }
}