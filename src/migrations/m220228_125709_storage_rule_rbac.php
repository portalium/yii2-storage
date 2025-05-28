<?php

use yii\db\Migration;
use portalium\storage\rbac\OwnRule;

class m220228_125709_storage_rule_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $rule = new OwnRule();
        $auth->add($rule);
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');

        $permissions = [
        'storageApiDefaultView',
        'storageApiDefaultCreate',
        'storageApiDefaultUpdate',
        'storageApiDefaultDelete',
        'storageApiDefaultIndex',
        'storageWebDefaultIndex',
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
        'storageWebDefaultdeleteFolderRecursive'
    ];

        foreach ($permissions as $permissionKey) {
        $permissionOwn = $auth->createPermission($permissionKey . 'Own');
            $permissionOwn->description = $permissionKey . ' Own';
            $permissionOwn->ruleName = $rule->name;
            $auth->add($permissionOwn);
            $auth->addChild($admin, $permissionOwn);
            $permission = $auth->getPermission($permissionKey);
            $auth->addChild($permissionOwn, $permission);
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;

        $permissions = [
        'storageApiDefaultView',
        'storageApiDefaultCreate',
        'storageApiDefaultUpdate',
        'storageApiDefaultDelete',
        'storageApiDefaultIndex',
        'storageWebDefaultIndex',
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
        'storageWebDefaultdeleteFolderRecursive'
    ];

        foreach ($permissions as $permissionKey) {
        $permissionOwn = $auth->getPermission($permissionKey . 'Own');
            $auth->remove($permissionOwn);
        }
    }
}
