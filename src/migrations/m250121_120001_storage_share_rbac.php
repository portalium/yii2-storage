<?php
use portalium\db\Migration;

class m250121_120001_storage_share_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $role = Yii::$app->setting->getValue('site::admin_role');

        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $user = $auth->getRole('user');

        $permissionNames = [
            // Web permissions
            'storageWebDefaultShareFile',
            'storageWebDefaultShareDirectory', 
            'storageWebDefaultShareFullStorage',
            'storageWebDefaultManageShares',
            'storageWebDefaultRevokeShare',
            'storageWebDefaultUpdateSharePermission',
            'storageWebDefaultViewShares',
            
            // API permissions
            'storageApiDefaultShareFile',
            'storageApiDefaultShareDirectory',
            'storageApiDefaultShareFullStorage',
            'storageApiDefaultRevokeShare',
            'storageApiDefaultGetShares',
        ];

        foreach ($permissionNames as $permissionName) {
            if (!$auth->getPermission($permissionName)) {
                $permission = $auth->createPermission($permissionName);
                $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionName);
                $permission->description = ucfirst($description);
                $auth->add($permission);
                $auth->addChild($admin, $permission);
            }
        }

        // Add some user permissions for own content
        $userPermissions = [
            'storageWebDefaultShareFile',
            'storageWebDefaultShareDirectory',
            'storageWebDefaultViewShares',
            'storageApiDefaultShareFile',
            'storageApiDefaultShareDirectory',
            'storageApiDefaultGetShares',
        ];

        foreach ($userPermissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission && $user) {
                $auth->addChild($user, $permission);
            }
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        $permissionNames = [
            'storageWebDefaultShareFile',
            'storageWebDefaultShareDirectory',
            'storageWebDefaultShareFullStorage',
            'storageWebDefaultManageShares',
            'storageWebDefaultRevokeShare',
            'storageWebDefaultUpdateSharePermission',
            'storageWebDefaultViewShares',
            'storageApiDefaultShareFile',
            'storageApiDefaultShareDirectory',
            'storageApiDefaultShareFullStorage',
            'storageApiDefaultRevokeShare',
            'storageApiDefaultGetShares',
        ];

        foreach ($permissionNames as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission) {
                $auth->remove($permission);
            }
        }
    }
}
