<?php

namespace portalium\storage\bundles;

use yii\web\AssetBundle;

/**
 * Share Modal Asset Bundle
 */
class ShareModalAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/share-modal';
    
    public $css = [
        'css/share-modal.css',
    ];
    
    public $js = [
        'js/share-modal.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
    ];
}
