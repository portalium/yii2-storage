<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class StorageAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/';

    public $css = [
        'modal.css'
    ];
    public $js = [
        'storage.js',
        'storageActions.js'
    ];
    public $publishOptions = [
        'forceCopy' => YII_DEBUG
    ];
    public $cssOptions = [
        'appendTimestamp' => true,
    ];
//  
    public $jsOptions = [
        'appendTimestamp' => true,
    ];

    public $depends = [
        'portalium\theme\bundles\AppAsset',
    ];
}