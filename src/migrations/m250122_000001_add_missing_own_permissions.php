<?php

use yii\db\Migration;
use portalium\storage\rbac\OwnRule;

class m250122_000001_add_missing_own_permissions extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $rule = $auth->getRule('storageOwnRule');
        
        if (!$rule) {
            $rule = new OwnRule();
            $auth->add($rule);
        }
        
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $user = $auth->getRole('user');

        // Web permissions that need Own variant
        $webPermissions = [
            'storageWebDefaultManageShares',
            'storageWebDefaultViewShares',
            'storageWebDefaultRevokeShare',
            'storageWebDefaultShareDirectory',
            'storageWebDefaultShareFullStorage',
            'storageWebDefaultUpdateSharePermission',
        ];

        foreach ($webPermissions as $permissionKey) {
            $permission = $auth->getPermission($permissionKey);
            
            // Create base permission if it doesn't exist
            if (!$permission) {
                $permission = $auth->createPermission($permissionKey);
                $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey);
                $permission->description = ucfirst($description);
                $auth->add($permission);
                $auth->addChild($admin, $permission);
            }
            
            // Create Own permission
            $permissionOwn = $auth->createPermission($permissionKey . 'Own');
            $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey . 'Own');
            $permissionOwn->description = ucfirst($description);
            $permissionOwn->ruleName = $rule->name;
            $auth->add($permissionOwn);
            $auth->addChild($admin, $permissionOwn);
            
            if ($user) {
                $auth->addChild($user, $permissionOwn);
            }
            
            $auth->addChild($permissionOwn, $permission);
        }

        // API permissions that need Own variant
        $apiPermissions = [
            'storageApiDefaultRevokeShare',
            'storageApiDefaultShareDirectory',
            'storageApiDefaultShareFullStorage',
        ];

        foreach ($apiPermissions as $permissionKey) {
            $permission = $auth->getPermission($permissionKey);
            
            // Create base permission if it doesn't exist
            if (!$permission) {
                $permission = $auth->createPermission($permissionKey);
                $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey);
                $permission->description = ucfirst($description);
                $auth->add($permission);
                $auth->addChild($admin, $permission);
            }
            
            // Create Own permission
            $permissionOwn = $auth->createPermission($permissionKey . 'Own');
            $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey . 'Own');
            $permissionOwn->description = ucfirst($description);
            $permissionOwn->ruleName = $rule->name;
            $auth->add($permissionOwn);
            $auth->addChild($admin, $permissionOwn);
            
            if ($user) {
                $auth->addChild($user, $permissionOwn);
            }
            
            $auth->addChild($permissionOwn, $permission);
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;

        $permissions = [
            // Web permissions
            'storageWebDefaultManageSharesOwn',
            'storageWebDefaultViewSharesOwn',
            'storageWebDefaultRevokeShareOwn',
            'storageWebDefaultShareDirectoryOwn',
            'storageWebDefaultShareFullStorageOwn',
            'storageWebDefaultUpdateSharePermissionOwn',
            
            // API permissions
            'storageApiDefaultRevokeShareOwn',
            'storageApiDefaultShareDirectoryOwn',
            'storageApiDefaultShareFileOwn',
            'storageApiDefaultShareFullStorageOwn',
        ];

        foreach ($permissions as $permissionKey) {
            $permission = $auth->getPermission($permissionKey);
            if ($permission) {
                $auth->remove($permission);
            }
        }
    }
}
