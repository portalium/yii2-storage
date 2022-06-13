<?php

use yii\db\Schema;
use yii\db\Migration;

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

        $this->createTable('{{%storage}}',[
            'id_storage'=> $this->primaryKey(11),
            'name'=> $this->string(255)->notNull(),
            'title'=> $this->string(255)->notNull(),
            'id_user'=> $this->integer(11)->notNull(),
            'mime_type'=> $this->integer(11)->notNull(),
        ], $tableOptions);

    }

    public function safeDown()
    {
            $this->dropTable('{{%storage}}');
    }
}
