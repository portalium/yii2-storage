<?php

use portalium\db\Migration;

class m260916_000004_storage_provider_permissions extends Migration
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
            'storageWebProviderIndex',
            'storageWebProviderCreate',
            'storageWebProviderUpdate',
            'storageWebProviderDelete',
            'storageWebProviderTest',
        ];
        foreach ($permissionNames as $permissionName) {
            if ($auth->getPermission($permissionName) !== null) {
                continue;
            }
            $permission = $auth->createPermission($permissionName);
            $permission->description = ucfirst(str_replace('storage', '', $permissionName));
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permissionNames = [
            'storageWebProviderIndex',
            'storageWebProviderCreate',
            'storageWebProviderUpdate',
            'storageWebProviderDelete',
            'storageWebProviderTest',
        ];
        foreach ($permissionNames as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission !== null) {
                $auth->remove($permission);
            }
        }
    }
}
