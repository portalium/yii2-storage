<?php

use yii\db\Migration;
use portalium\site\Module;
use yii\helpers\ArrayHelper;
use portalium\site\models\Form;

class m110523_200102_storage_setting extends Migration
{
    public function up()
    {
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'storage',
            'name' => 'storage::workspace::default_role',
            'label' => 'Workspace Default Role',
            'value' => '0',
            'type' => Form::TYPE_DROPDOWNLIST,
            'config' => json_encode([
                'method' => [
                    'class' => \Yii::$app->workspace->className(),
                    'name' => 'getAvailableRoles',
                    'map' => [
                        'key' => 'name',
                        'value' => 'name'
                    ],
                    'params' => [
                        'module' => 'storage'
                    ]
                ]
            ])
        ]);

        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'storage',
            'name' => 'storage::workspace::admin_role',
            'label' => 'Workspace Admin Role',
            'value' => '0',
            'type' => Form::TYPE_DROPDOWNLIST,
            'config' => json_encode([
                'method' => [
                    'class' => \Yii::$app->workspace->className(),
                    'name' => 'getAvailableRoles',
                    'map' => [
                        'key' => 'name',
                        'value' => 'name'
                    ],
                    'params' => [
                        'module' => 'storage'
                    ]
                ]
            ])
        ]);
    }

    public function down()
    {
        $this->delete(Module::$tablePrefix . 'setting', ['id_module' => 'storage']);
    }
}
