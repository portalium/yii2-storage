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
            $permissionOwn = $auth->createPermission($permissionKey . 'Own');
            $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey . 'Own');
            $permissionOwn->description = ucfirst($description);
            $permissionOwn->ruleName = $rule->name;
            $auth->add($permissionOwn);
            $auth->addChild($admin, $permissionOwn);
            
            if ($user) {
                $auth->addChild($user, $permissionOwn);
            }
            
            $permission = $auth->getPermission($permissionKey);
            if (!$permission) {
                $permission = $auth->createPermission($permissionKey);
                $permission->description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionKey);
                $permission->description = ucfirst($permission->description);
                $auth->add($permission);
            }
            try {            $auth->addChild($permissionOwn, $permission);

                //code...
            } catch (\Throwable $th) {
                
                //throw $th;
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