<?php

use portalium\db\Migration;

class m260916_000007_storage_provider_system_permission extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $permissionNames = [
            'storageWebProviderSystem',
        ];
        foreach ($permissionNames as $permissionName) {
            if ($auth->getPermission($permissionName) !== null) {
                continue;
            }
            $permission = $auth->createPermission($permissionName);
            $permission->description = 'Define a provider available to the whole site';
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('storageWebProviderSystem');
        if ($permission !== null) {
            $auth->remove($permission);
        }
    }
}
