<?php

use yii\db\Migration;

class m261014_000013_storage_user_share_own_permissions extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $user = $auth->getRole('user');
        if ($user === null) {
            return true;
        }
        $permissionNames = [
            'storageWebDefaultShareFile',
            'storageWebDefaultShareDirectory',
            'storageApiDefaultShareFile',
            'storageApiDefaultShareDirectory',
        ];
        foreach ($permissionNames as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission !== null && $auth->hasChild($user, $permission)) {
                $auth->removeChild($user, $permission);
            }
            $permissionOwn = $auth->getPermission($permissionName . 'Own');
            if ($permissionOwn !== null && $permissionOwn->ruleName !== null && !$auth->hasChild($user, $permissionOwn)) {
                $auth->addChild($user, $permissionOwn);
            }
        }
        return true;
    }

    public function safeDown()
    {
        return true;
    }
}
