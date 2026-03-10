<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class JSTreeAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/js-tree';

    public $js = [
        'js/jstree.min.js'
    ];

    public $css = [
        'themes/style.min.css'
    ];

    public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];

    public function init(): void
    {
        parent::init();
    }
}
