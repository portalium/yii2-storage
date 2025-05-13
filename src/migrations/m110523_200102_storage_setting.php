<?php

use portalium\workspace\models\WorkspaceUser;
use yii\db\Migration;
use portalium\site\Module;
use portalium\site\models\Form;

class m110523_200102_storage_setting extends Migration
{
    public function up()
    {
        $siteUserRole = Yii::$app->setting->getValue('site::user_role');
        $siteAdminRole = Yii::$app->setting->getValue('site::admin_role');
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'storage',
            'name' => 'storage::workspace::default_role',
            'label' => 'Workspace Default Role',
            'value' => $siteUserRole ? $siteUserRole : '0',
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
            'value' => $siteAdminRole ? $siteAdminRole : '0',
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

        $roles = [
            $siteUserRole,
            $siteAdminRole
        ];
        foreach ($roles as $role) {
            $workspaceUser = new WorkspaceUser();
            $workspaceUser->id_user = 1;
            $workspaceUser->id_workspace = 1;
            $workspaceUser->role = $role;
            $workspaceUser->id_module = 'storage';
            $workspaceUser->status = $role == $siteAdminRole ? WorkspaceUser::STATUS_ACTIVE : WorkspaceUser::STATUS_INACTIVE;
            $workspaceUser->save();
        }
    }

    public function down()
    {
        $this->delete(Module::$tablePrefix . 'setting', ['id_module' => 'storage']);
    }
}
