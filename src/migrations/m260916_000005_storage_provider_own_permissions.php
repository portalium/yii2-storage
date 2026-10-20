<?php

use portalium\db\Migration;
use portalium\storage\rbac\OwnRule;

class m260916_000005_storage_provider_own_permissions extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $rule = new OwnRule();
        if ($auth->getRule($rule->name) === null) {
            $auth->add($rule);
        }
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $permissionsName = [
            'storageWebProviderUpdateOwn',
            'storageWebProviderDeleteOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            if ($auth->getPermission($permissionName) !== null) {
                continue;
            }
            $permission = $auth->createPermission($permissionName);
            $permission->description = $permissionName;
            $permission->ruleName = $rule->name;
            $auth->add($permission);
            $auth->addChild($admin, $permission);
            $childPermission = $auth->getPermission(str_replace('Own', '', $permissionName));
            if ($childPermission !== null) {
                $auth->addChild($permission, $childPermission);
            }
        }

        $permissionsName = [
            'storageWebProviderIndexOwn',
            'storageWebProviderCreateOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            if ($auth->getPermission($permissionName) !== null) {
                continue;
            }
            $permission = $auth->createPermission($permissionName);
            $permission->description = $permissionName;
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permissionsName = [
            'storageWebProviderUpdateOwn',
            'storageWebProviderDeleteOwn',
            'storageWebProviderIndexOwn',
            'storageWebProviderCreateOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission !== null) {
                $auth->remove($permission);
            }
        }
    }
}
