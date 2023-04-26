<?php

use yii\db\Migration;

class m220228_125709_storage_rule_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;

        $rule = $auth->getRule('WorkspaceCheckRule');

        $role = Yii::$app->setting->getValue('default::role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $endPrefix = 'ForWorkspace';
        $permissions = [


        ];


        foreach ($permissions as $permissionKey => $permissionDescription) {
            $permissionForWorkspace = $auth->createPermission($permissionKey . $endPrefix);
            $permissionForWorkspace->description = ' (' . $endPrefix . ')' . $permissionDescription;
            $permissionForWorkspace->ruleName = $rule->name;
            $auth->add($permissionForWorkspace);
            $auth->addChild($admin, $permissionForWorkspace);
            $permission = $auth->getPermission($permissionKey);
            $auth->addChild($permissionForWorkspace, $permission);

        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        $endPrefix = 'ForWorkspace';
        $permissions = [

        ];

        foreach ($permissions as $permission) {
            $permissionForWorkspace = $auth->getPermission($permission . $endPrefix);
            $auth->remove($permissionForWorkspace);
        }
    }
}
