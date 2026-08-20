<?php

use yii\db\Migration;

class m260325_000000_storage_move_permissions extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');

        $permissionNames = [
            'storageWebDefaultMoveItems',
            'storageWebDefaultMoveModal',
        ];
        foreach ($permissionNames as $permissionName) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = ucfirst(str_replace('storageWebDefault', '', $permissionName));
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permissionNames = [
            'storageWebDefaultMoveItems',
            'storageWebDefaultMoveModal',
        ];
        foreach ($permissionNames as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission) {
                $auth->remove($permission);
            }
        }
        return true;
    }
}
