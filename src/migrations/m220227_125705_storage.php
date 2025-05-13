<?php

use portalium\db\Migration;
use portalium\storage\Module;
use portalium\user\Module as UserModule;

class m220227_125705_storage extends Migration
{

    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB';

        $this->createTable('{{%' . Module::$tablePrefix . 'storage_directory}}', [
            'id_directory' => $this->primaryKey(11),
            'id_parent' => $this->integer(11)->null(),
            'name' => $this->string(256)->notNull(),
            'date_create' => $this->datetime()->notNull()->defaultExpression("CURRENT_TIMESTAMP"),
            'date_update' => $this->datetime()->notNull()->defaultExpression("CURRENT_TIMESTAMP"),
        ], $tableOptions);

        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage_directory-id_parent}}',
            '{{%' . Module::$tablePrefix . 'storage_directory}}',
            'id_parent',
            '{{%' . Module::$tablePrefix . 'storage_directory}}',
            'id_directory',
            'SET NULL'
        );

        $this->createTable('{{%' . Module::$tablePrefix . 'storage}}',[
            'id_storage'=> $this->primaryKey(11),
            'name'=> $this->string(255)->notNull(),
            'title'=> $this->string(255)->notNull(),
            'id_user'=> $this->integer(11)->notNull(),
            'mime_type'=> $this->integer(11)->notNull(),
            'hash_file'=> $this->string(255)->null(),
            'id_directory' => $this->integer(11)->null(),
            'date_create'=> $this->datetime()->notNull()->defaultExpression("CURRENT_TIMESTAMP"),
            'date_update'=> $this->datetime()->notNull()->defaultExpression("CURRENT_TIMESTAMP"),
        ], $tableOptions);

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage-id_user}}',
            '{{%' . Module::$tablePrefix . 'storage}}',
            'id_user'
        );

        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_user}}',
            '{{%' . Module::$tablePrefix . 'storage}}',
            'id_user',
            '{{%' . UserModule::$tablePrefix . 'user}}',
            'id_user',
            'RESTRICT'
        );

        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_directory}}',
            '{{%' . Module::$tablePrefix . 'storage}}',
            'id_directory',
            '{{%' . Module::$tablePrefix . 'storage_directory}}',
            'id_directory',
            'SET NULL'
        );
    }


    public function safeDown()
    {
        $this->dropForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_user}}',
            '{{%' . Module::$tablePrefix . 'storage}}'
        );

        $this->dropForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_directory}}',
            '{{%' . Module::$tablePrefix . 'storage}}'
        );

        $this->dropTable('{{%' . Module::$tablePrefix . 'storage}}');

        $this->dropForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage_directory-id_parent}}',
            '{{%' . Module::$tablePrefix . 'storage_directory}}'
        );

        $this->dropTable('{{%' . Module::$tablePrefix . 'storage_directory}}');
    }

}
