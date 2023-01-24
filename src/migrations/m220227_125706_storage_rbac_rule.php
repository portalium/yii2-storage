<?php

use portalium\storage\rbac\OwnRule;
use yii\db\Migration;


class m220227_125706_storage_rbac_rule extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $rule = new OwnRule();
        $auth->add($rule);
        $settings = yii\helpers\ArrayHelper::map(portalium\site\models\Setting::find()->asArray()->all(),'name','value');
        $role = 'admin';
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $permissionsName = [
            'storageApiDefaultViewOwn',
            'storageApiDefaultUpdateOwn',
            'storageApiDefaultDeleteOwn',
            'storageWebDefaultViewOwn',
            'storageWebDefaultUpdateOwn',
            'storageWebDefaultDeleteOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = $permissionName;
            $permission->ruleName = $rule->name;
            $auth->add($permission);
            $auth->addChild($admin, $permission);
            $childPermission = $auth->getPermission(str_replace('Own', '', $permissionName));
            $auth->addChild($permission, $childPermission);
        }
        $permissionsName = [
            'storageApiDefaultIndexOwn',
            'storageWebDefaultIndexOwn',
        ];

        foreach ($permissionsName as $permissionName) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = $permissionName;
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;

        $auth->remove($auth->getPermission('storageWebDefaultIndex'));
        $auth->remove($auth->getPermission('storageWebDefaultView'));
        $auth->remove($auth->getPermission('storageWebDefaultCreate'));
        $auth->remove($auth->getPermission('storageWebDefaultUpdate'));
        $auth->remove($auth->getPermission('storageWebDefaultDelete'));

    }
}