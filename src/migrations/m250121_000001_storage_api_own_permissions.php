<?php

use yii\db\Migration;
use portalium\storage\rbac\OwnRule;

class m250121_000001_storage_api_own_permissions extends Migration
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

        // API Own izinleri
        $permissions = [
            'storageApiDefaultView' => 'API: View own storage',
            'storageApiDefaultGetFile' => 'API: Get own file',
        ];

        foreach ($permissions as $permissionKey => $description) {
            $permissionOwn = $auth->createPermission($permissionKey . 'Own');
            $permissionOwn->description = $description;
            $permissionOwn->ruleName = $rule->name;
            $auth->add($permissionOwn);
            $auth->addChild($admin, $permissionOwn);
            
            if ($user) {
                $auth->addChild($user, $permissionOwn);
            }
            
            $permission = $auth->getPermission($permissionKey);
            if ($permission) {
                $auth->addChild($permissionOwn, $permission);
            }
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;

        $permissions = [
            'storageApiDefaultView',
            'storageApiDefaultGetFile',
        ];

        foreach ($permissions as $permissionKey) {
            $permissionOwn = $auth->getPermission($permissionKey . 'Own');
            if ($permissionOwn) {
                $auth->remove($permissionOwn);
            }
        }
    }
}
