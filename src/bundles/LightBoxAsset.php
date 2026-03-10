<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class LightBoxAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/light-box';

    public $depends = [
        'portalium\theme\bundles\AppAsset'
    ];

    public $js = [
        'js/lightBox.js'
    ];

    public $css = [
        'css/lightBox.css'
    ];

    public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];

    public function init(): void
    {
        parent::init();
    }
}
