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
        'storage.js'
    ];

    public $cssOptions = [
        'appendTimestamp' => true,
    ];
//

    public $depends = [
        'portalium\theme\bundles\AppAsset',
    ];
}