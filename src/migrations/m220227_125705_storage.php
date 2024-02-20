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

        $this->createTable('{{%' . Module::$tablePrefix . 'storage}}',[
            'id_storage'=> $this->primaryKey(11),
            'name'=> $this->string(255)->notNull(),
            'title'=> $this->string(255)->notNull(),
            'id_user'=> $this->integer(11)->notNull(),
            'mime_type'=> $this->integer(11)->notNull(),
            'hash_file'=> $this->string(255)->null(),
        ], $tableOptions);

        // creates index for column `id_user`
        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage-id_user}}',
            '{{%' . Module::$tablePrefix . 'storage}}',
            'id_user'
        );

        // add foreign key for table `{{%user}}`
        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_user}}',
            '{{%' . Module::$tablePrefix . 'storage}}',
            'id_user',
            '{{%' . UserModule::$tablePrefix . 'user}}',
            'id_user',
            'RESTRICT'
        );
    }

    public function safeDown()
    {
            $this->dropTable('{{%' . Module::$tablePrefix . 'storage}}');
    }
}
