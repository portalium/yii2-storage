<?php

use yii\db\Migration;

class m260928_000001_storage_unlink_api_index_own extends Migration
{
    public function up()
    {
        $auth = \Yii::$app->authManager;
        $own = $auth->getPermission('storageApiDefaultIndexOwn');
        $base = $auth->getPermission('storageApiDefaultIndex');
        if ($own !== null && $base !== null && $auth->hasChild($own, $base)) {
            $auth->removeChild($own, $base);
        }
    }

    public function down()
    {
        $auth = \Yii::$app->authManager;
        $own = $auth->getPermission('storageApiDefaultIndexOwn');
        $base = $auth->getPermission('storageApiDefaultIndex');
        if ($own !== null && $base !== null && !$auth->hasChild($own, $base)) {
            $auth->addChild($own, $base);
        }
    }
}
