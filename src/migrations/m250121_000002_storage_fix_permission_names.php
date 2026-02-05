<?php

use portalium\db\Migration;

class m250121_000002_storage_fix_permission_names extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        
        // Yanlış yazılmış izin (küçük 'd' ile başlıyor)
        $oldPermission = $auth->getPermission('storageWebDefaultdeleteFolderRecursive');
        $oldPermissionOwn = $auth->getPermission('storageWebDefaultdeleteFolderRecursiveOwn');
        
        // Doğru yazılmış izinlerin var olup olmadığını kontrol et
        $newPermission = $auth->getPermission('storageWebDefaultDeleteFolderRecursive');
        $newPermissionOwn = $auth->getPermission('storageWebDefaultDeleteFolderRecursiveOwn');
        
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        
        // Base permission işlemi
        if ($oldPermission && !$newPermission) {
            // Yeni doğru isimdeki izni oluştur
            $newPermission = $auth->createPermission('storageWebDefaultDeleteFolderRecursive');
            $newPermission->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', 'storageWebDefaultDeleteFolderRecursive'));
            $auth->add($newPermission);
            $auth->addChild($admin, $newPermission);
            
            // Eski izni kaldır
            $auth->remove($oldPermission);
        } elseif ($oldPermission && $newPermission) {
            // Her ikisi de varsa, sadece eskisini kaldır
            $auth->remove($oldPermission);
        }
        
        // Own permission işlemi
        if ($oldPermissionOwn && !$newPermissionOwn) {
            $rule = $auth->getRule('storageOwnRule');
            $user = $auth->getRole('user');
            
            // Yeni doğru isimdeki Own izni oluştur
            $newPermissionOwn = $auth->createPermission('storageWebDefaultDeleteFolderRecursiveOwn');
            $newPermissionOwn->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', 'storageWebDefaultDeleteFolderRecursiveOwn'));
            $newPermissionOwn->ruleName = $rule->name;
            $auth->add($newPermissionOwn);
            $auth->addChild($admin, $newPermissionOwn);
            
            if ($user) {
                $auth->addChild($user, $newPermissionOwn);
            }
            
            // Yeni base permission'a bağla
            $newPermission = $auth->getPermission('storageWebDefaultDeleteFolderRecursive');
            if ($newPermission) {
                $auth->addChild($newPermissionOwn, $newPermission);
            }
            
            // Eski izni kaldır
            $auth->remove($oldPermissionOwn);
        } elseif ($oldPermissionOwn && $newPermissionOwn) {
            // Her ikisi de varsa, sadece eskisini kaldır
            $auth->remove($oldPermissionOwn);
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        
        // Yeni izinleri kaldır
        $newPermission = $auth->getPermission('storageWebDefaultDeleteFolderRecursive');
        if ($newPermission) {
            $auth->remove($newPermission);
        }
        
        $newPermissionOwn = $auth->getPermission('storageWebDefaultDeleteFolderRecursiveOwn');
        if ($newPermissionOwn) {
            $auth->remove($newPermissionOwn);
        }
        
        // Eski yanlış yazılmış izinleri geri yükle
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        
        $oldPermission = $auth->createPermission('storageWebDefaultdeleteFolderRecursive');
        $oldPermission->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', 'storageWebDefaultdeleteFolderRecursive'));
        $auth->add($oldPermission);
        $auth->addChild($admin, $oldPermission);
        
        $rule = $auth->getRule('storageOwnRule');
        $user = $auth->getRole('user');
        
        $oldPermissionOwn = $auth->createPermission('storageWebDefaultdeleteFolderRecursiveOwn');
        $oldPermissionOwn->description = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', 'storageWebDefaultdeleteFolderRecursiveOwn'));
        $oldPermissionOwn->ruleName = $rule->name;
        $auth->add($oldPermissionOwn);
        $auth->addChild($admin, $oldPermissionOwn);
        
        if ($user) {
            $auth->addChild($user, $oldPermissionOwn);
        }
        
        $permission = $auth->getPermission('storageWebDefaultdeleteFolderRecursive');
        if ($permission) {
            $auth->addChild($oldPermissionOwn, $permission);
        }
    }
}
