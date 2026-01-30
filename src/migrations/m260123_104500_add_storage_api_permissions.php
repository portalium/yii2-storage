<?php

use portalium\storage\rbac\OwnRule;
use yii\db\Migration;

class m260123_104500_add_storage_api_permissions extends Migration
{
    public function safeUp()
    {
        $auth = \Yii::$app->authManager;

        // Admin and User roles
        $admin = $auth->getRole('admin');
        $user = $auth->getRole('user');

        // Own Rule for user-specific permissions
        $ownRule = $auth->getRule('storageOwnRule');
        if (!$ownRule) {
            $ownRule = new OwnRule();
            $auth->add($ownRule);
        }

        // storageApiDefaultUpdate
        $permission = $auth->createPermission('storageApiDefaultUpdate');
        $permission->description = 'Update storage file via API';
        $auth->add($permission);
        $auth->addChild($admin, $permission);

        $permissionOwn = $auth->createPermission('storageApiDefaultUpdateOwn');
        $permissionOwn->description = 'Update own storage file via API';
        $permissionOwn->ruleName = $ownRule->name;
        $auth->add($permissionOwn);
        $auth->addChild($permissionOwn, $permission);
        $auth->addChild($user, $permissionOwn);

        // storageApiDefaultDelete
        $permission = $auth->createPermission('storageApiDefaultDelete');
        $permission->description = 'Delete storage file via API';
        $auth->add($permission);
        $auth->addChild($admin, $permission);

        $permissionOwn = $auth->createPermission('storageApiDefaultDeleteOwn');
        $permissionOwn->description = 'Delete own storage file via API';
        $permissionOwn->ruleName = $ownRule->name;
        $auth->add($permissionOwn);
        $auth->addChild($permissionOwn, $permission);
        $auth->addChild($user, $permissionOwn);

        // storageApiDefaultUpload
        $permission = $auth->createPermission('storageApiDefaultUpload');
        $permission->description = 'Upload file via API';
        $auth->add($permission);
        $auth->addChild($admin, $permission);
        $auth->addChild($user, $permission);

        // ============================================
        // API PERMISSIONS - Directory Management
        // ============================================

        // storageApiDefaultManageDirectory
        $permission = $auth->createPermission('storageApiDefaultManageDirectory');
        $permission->description = 'Manage storage directory via API';
        $auth->add($permission);
        $auth->addChild($admin, $permission);

        $permissionOwn = $auth->createPermission('storageApiDefaultManageDirectoryOwn');
        $permissionOwn->description = 'Manage own storage directory via API';
        $permissionOwn->ruleName = $ownRule->name;
        $auth->add($permissionOwn);
        $auth->addChild($permissionOwn, $permission);
        $auth->addChild($user, $permissionOwn);

        return true;
    }

    public function safeDown()
    {
        $auth = \Yii::$app->authManager;

        // Remove all API permissions
        $permissions = [
            'storageApiDefaultUpdate',
            'storageApiDefaultUpdateOwn',
            'storageApiDefaultDelete',
            'storageApiDefaultDeleteOwn',
            'storageApiDefaultUpload',
            'storageApiDefaultGetFile',
            'storageApiDefaultGetFileOwn',
            'storageApiDefaultManageDirectory',
            'storageApiDefaultManageDirectoryOwn',
        ];

        foreach ($permissions as $permName) {
            $permission = $auth->getPermission($permName);
            if ($permission) {
                $auth->remove($permission);
            }
        }

        return true;
    }
}
