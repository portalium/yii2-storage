<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class JSTreeAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/';

    public $js = [
        'jstree.min.js'
    ];

    public $css = [
        'themes/default/style.min.css'
    ];

    public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];

    public function init()
    {
        parent::init();
    }
}
