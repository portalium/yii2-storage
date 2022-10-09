<?php

use yii\db\Migration;
//use portalium\site\models\Form;

class m220227_125705_storage_setting extends Migration
{
    public function up()
    {
        $this->insert('site_setting', [
            'module' => 'storage',
            'name' => 'app::data',
            'label' => 'Default Data Folder',
            'value' => 'data',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => ''
        ]);

    }

    public function down()
    {
        //$this->dropTable('site_setting');
        //kayıt silinecek
    }
}
