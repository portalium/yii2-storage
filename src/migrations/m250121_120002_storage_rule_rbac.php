<?php

use yii\db\Migration;
use portalium\storage\rbac\OwnRule;

class m250121_120002_storage_rule_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $rule = new OwnRule();
        if (!$auth->getRule($rule->name)) {
            $auth->add($rule);
        } 
        
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $user = $auth->getRole('user');

        $permissions = [
            'storageWebDefaultUploadFile',
            'storageWebDefaultDownloadFile',
            'storageWebDefaultRenameFile',
            'storageWebDefaultUpdateFile',
            'storageWebDefaultShareFile',
            'storageWebDefaultCopyFile',
            'storageWebDefaultDeleteFile',
            'storageWebDefaultPickerModal',
            'storageWebDefaultFileList',
            'storageWebDefaultSearch',
            'storageWebDefaultNewFolder',
            'storageWebDefaultRenameFolder',
            'storageWebDefaultDeleteFolder',
            'storageWebDefaultdeleteFolderRecursive',
            'storageWebDefaultManageDirectory',
            'storageWebDefaultGetFile',
        ];

        foreach ($permissions as $permissionKey) {
            $permissionOwn = $auth->getPermission($permissionKey . 'Own');
            if (!$permissionOwn) {
                $permissionOwn = $auth->createPermission($permissionKey . 'Own');
                $permissionOwn->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey . 'Own'));
                $permissionOwn->ruleName = $rule->name;
                $auth->add($permissionOwn);
            }

            if ($admin && !$auth->hasChild($admin, $permissionOwn)) {
                $auth->addChild($admin, $permissionOwn);
            }
            if ($user && !$auth->hasChild($user, $permissionOwn)) {
                $auth->addChild($user, $permissionOwn);
            }

            $permission = $auth->getPermission($permissionKey);
            if (!$permission) {
                $permission = $auth->createPermission($permissionKey);
                $permission->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey));
                $auth->add($permission);
            }

            if (!$auth->hasChild($permissionOwn, $permission)) {
                $auth->addChild($permissionOwn, $permission);
            }
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;

        $permissions = [
            'storageWebDefaultUploadFile',
            'storageWebDefaultDownloadFile',
            'storageWebDefaultRenameFile',
            'storageWebDefaultUpdateFile',
            'storageWebDefaultShareFile',
            'storageWebDefaultCopyFile',
            'storageWebDefaultDeleteFile',
            'storageWebDefaultPickerModal',
            'storageWebDefaultFileList',
            'storageWebDefaultSearch',
            'storageWebDefaultNewFolder',
            'storageWebDefaultRenameFolder',
            'storageWebDefaultDeleteFolder',
            'storageWebDefaultdeleteFolderRecursive',
            'storageWebDefaultManageDirectory',
            'storageWebDefaultGetFile',
        ];

        foreach ($permissions as $permissionKey) {
            $permissionOwn = $auth->getPermission($permissionKey . 'Own');
            if ($permissionOwn) {
                $auth->remove($permissionOwn);
            }
        }
    }
}