<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class IconAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/icons/';

    public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];

    public function init()
    {
        parent::init();
    }
}
