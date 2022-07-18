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
        
        $storageWebDefaultIndex = $auth->createPermission('storageWebDefaultIndex');
        $storageWebDefaultIndex->description = 'Storage Web Default Index';
        $auth->add($storageWebDefaultIndex);
        $auth->addChild($admin, $storageWebDefaultIndex);

        $storageWebDefaultView = $auth->createPermission('storageWebDefaultView');
        $storageWebDefaultView->description = 'Storage Web Default View';
        $auth->add($storageWebDefaultView);
        $auth->addChild($admin, $storageWebDefaultView);

        $storageWebDefaultCreate = $auth->createPermission('storageWebDefaultCreate');
        $storageWebDefaultCreate->description = 'Storage Web Default Create';
        $auth->add($storageWebDefaultCreate);
        $auth->addChild($admin, $storageWebDefaultCreate);

        $storageWebDefaultUpdate = $auth->createPermission('storageWebDefaultUpdate');
        $storageWebDefaultUpdate->description = 'Storage Web Default Update';
        $auth->add($storageWebDefaultUpdate);
        $auth->addChild($admin, $storageWebDefaultUpdate);

        $storageWebDefaultDelete = $auth->createPermission('storageWebDefaultDelete');
        $storageWebDefaultDelete->description = 'Storage Web Default Delete';
        $auth->add($storageWebDefaultDelete);
        $auth->addChild($admin, $storageWebDefaultDelete);

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