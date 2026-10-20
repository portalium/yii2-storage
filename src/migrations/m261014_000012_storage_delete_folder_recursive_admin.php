<?php

use yii\db\Migration;

class m261014_000012_storage_delete_folder_recursive_admin extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $permissionName = 'storageWebDefaultDeleteFolderRecursive';
        $permission = $auth->getPermission($permissionName);
        if ($permission === null) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionName));
            $auth->add($permission);
        }
        if ($admin !== null && !$auth->hasChild($admin, $permission)) {
            $auth->addChild($admin, $permission);
        }
        return true;
    }

    public function safeDown()
    {
        return true;
    }
}
