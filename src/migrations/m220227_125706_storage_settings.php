<?php

use yii\db\Schema;
use yii\db\Migration;
use portalium\site\models\Form;

class m220227_125706_storage_settings extends Migration
{
    public function safeUp()
    {
        $this->insert('site_setting', [
            'module' => 'storage',
            'name' => 'app::logo',
            'label' => 'Application Logo',
            'value' => '0',
            'type' => Form::TYPE_WIDGET,
            'config' => json_encode([
                'widget' => '\portalium\storage\widgets\FilePicker',
                'options' => [
                    'multiple' => 0,
                    'returnAttribute' => ['name']
                ]
            ])
        ]);

    }

    public function safeDown()
    {
        $this->delete('site_setting', ['name' => 'app::logo']);
    }
}
