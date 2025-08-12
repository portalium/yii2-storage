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
            'storageWebDefaultIndex',
            'storageWebDefaultIndexOwn',
            'storageWebDefaultManage',
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
        ];

        foreach ($permissionNames as $permissionName) {
            $permission = $auth->createPermission($permissionName);
            $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $permissionName);
            $permission->description = ucfirst($description);
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        $permissionNames = [
            'storageWebDefaultIndex',
            'storageWebDefaultIndexOwn',
            'storageWebDefaultManage',
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
        ];

        foreach ($permissionNames as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission) {
                $auth->remove($permission);
            }
        }
    }
}