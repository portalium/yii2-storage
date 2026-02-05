<?php

use portalium\db\Migration;

class m250121_000000_storage_api_permissions extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        
        // Yeni API izinleri
        $permissionNames = [
            'storageApiDefaultIndex' => 'API: List all storage',
            'storageApiDefaultView' => 'API: View single storage',
            'storageApiDefaultGetFile' => 'API: Get file content',
        ];

        foreach ($permissionNames as $permissionName => $description) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = $description;
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        
        $permissionNames = [
            'storageApiDefaultIndex',
            'storageApiDefaultView',
            'storageApiDefaultGetFile',
        ];

        foreach ($permissionNames as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission) {
                $auth->remove($permission);
            }
        }
    }
}
