<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class ProviderAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/provider';

    public $js = [
        'js/provider.js',
    ];

    public $depends = [
        'portalium\theme\bundles\AppAsset',
    ];
}
