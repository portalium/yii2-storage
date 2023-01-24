<?php
use yii\db\Migration;

class m220227_125705_storage_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        
        $settings = yii\helpers\ArrayHelper::map(portalium\site\models\Setting::find()->asArray()->all(),'name','value');
        $role = 'admin';
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $permissionNames = [
            'storageApiDefaultView',
            'storageApiDefaultCreate',
            'storageApiDefaultUpdate',
            'storageApiDefaultDelete',
            'storageApiDefaultIndex',
            'storageWebDefaultIndex',
            'storageWebDefaultView',
            'storageWebDefaultCreate',
            'storageWebDefaultUpdate',
            'storageWebDefaultDelete',
        ];
        
        foreach ($permissionNames as $permissionName) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = ucfirst(str_replace('storage', '', $permissionName));
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