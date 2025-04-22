<?php
use portalium\db\Migration;

class m220227_125705_storage_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $permissionNames = [
            'storageApiDefaultView',
            'storageApiDefaultCreate',
            'storageApiDefaultUpdate',
            'storageApiDefaultDelete',
            'storageApiDefaultIndex',
            'storageWebDefaultIndex',
            'storageWebDefaultView',
            'storageWebDefaultGetFile',
            'storageWebDefaultCreate',
            'storageWebDefaultUpdate',
            'storageWebDefaultDelete',
            'storageStorageFindAll',
            'storageStorageFindOwner',
        ];
        
        foreach ($permissionNames as $permissionName) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = ucfirst(str_replace('storage', '', $permissionName));
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }        

    }

    public function down()
    {
        $auth = Yii::$app->authManager;

        $auth->remove($auth->getPermission('storageWebDefaultIndex'));
        $auth->remove($auth->getPermission('storageWebDefaultView'));
        $auth->remove($auth->getPermission('storageWebDefaultCreate'));
        $auth->remove($auth->getPermission('storageWebDefaultUpdate'));
        $auth->remove($auth->getPermission('storageWebDefaultDelete'));
        $auth->remove($auth->getPermission('storageApiDefaultView'));
        $auth->remove($auth->getPermission('storageWebDefaultGetFile'));
        $auth->remove($auth->getPermission('storageApiDefaultCreate'));
        $auth->remove($auth->getPermission('storageApiDefaultUpdate'));
        $auth->remove($auth->getPermission('storageApiDefaultDelete'));
        $auth->remove($auth->getPermission('storageApiDefaultIndex'));
        $auth->remove($auth->getPermission('storageStorageFindAll'));
        $auth->remove($auth->getPermission('storageStorageFindOwner'));

    }
}