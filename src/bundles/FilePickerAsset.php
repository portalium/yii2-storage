<?php
namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class FilePickerAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/'; 
    public $css = [
        'modal.css'
    ];
    public $js = [
        'storage.js'
    ];
    public $depends = [
        'yii\web\JqueryAsset',  
    ];
    public $cssOptions = [
        'appendTimestamp' => true,
    ];
}
