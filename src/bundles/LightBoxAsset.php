<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class LightBoxAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/portalium-storage/src/assets/';

    public $depends = [
        'portalium\theme\bundles\AppAsset'
    ];

    public $js = [
        'lightBox.js'
    ];

    public $css = [
        'lightBox.css'
    ];

    public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];

    public function init()
    {
        parent::init();
    }
}
