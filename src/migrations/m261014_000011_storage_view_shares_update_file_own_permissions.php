<?php

use yii\db\Migration;
use portalium\storage\rbac\OwnRule;

class m261014_000011_storage_view_shares_update_file_own_permissions extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $rule = $auth->getRule('storageOwnRule');
        if ($rule === null) {
            $rule = new OwnRule();
            $auth->add($rule);
        }
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $user = $auth->getRole('user');
        $permissionsName = [
            'storageWebDefaultViewSharesOwn',
            'storageWebDefaultUpdateFileOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission === null) {
                $permission = $auth->createPermission($permissionName);
                $permission->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionName));
                $permission->ruleName = $rule->name;
                $auth->add($permission);
            }
            foreach ([$admin, $user] as $parent) {
                if ($parent !== null && !$auth->hasChild($parent, $permission)) {
                    $auth->addChild($parent, $permission);
                }
            }
            $childPermission = $auth->getPermission(str_replace('Own', '', $permissionName));
            if ($childPermission !== null && !$auth->hasChild($permission, $childPermission)) {
                $auth->addChild($permission, $childPermission);
            }
        }
        return true;
    }

    public function safeDown()
    {
        return true;
    }
}
