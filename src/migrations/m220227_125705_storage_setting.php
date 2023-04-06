<?php

use portalium\db\Migration;
use portalium\storage\Module;
use portalium\site\Module as SiteModule;
use portalium\site\models\Form;

class m220227_125705_storage_setting extends Migration
{
    public function up()
    {
        $this->insert(SiteModule::$tablePrefix . 'setting', [
            'module' => 'storage',
            'name' => 'storage::path',
            'label' => 'Default Storage Path',
            'value' => 'data',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => ''
        ]);
    }

    public function down()
    {
        $this->dropTable(Module::$tablePrefix . 'setting');
    }
}
