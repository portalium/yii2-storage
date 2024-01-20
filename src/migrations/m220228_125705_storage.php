<?php

use portalium\storage\Module;
use yii\db\Migration;

class m220228_125705_storage extends Migration
{
    public function up()
    {
        $this->addColumn(Module::$tablePrefix . 'storage', 'id_workspace', $this->integer(11)->notNull()->defaultValue(0));
        // access
        $this->addColumn(Module::$tablePrefix . 'storage', 'access', $this->integer(11)->defaultValue(1));
    }

    public function down()
    {

        $this->dropColumn(Module::$tablePrefix . 'storage', 'id_workspace');

    }
}
