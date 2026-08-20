<?php
namespace portalium\storage\bundles;

use yii\web\AssetBundle;

class FilePickerAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-storage/src/assets/file-picker'; 
    public $css = [
        'css/file-picker.css',  
    ];
    public $js = [
        'js/file-picker.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'portalium\storage\bundles\StorageAsset',
    ];
    public $cssOptions = [
        'appendTimestamp' => true,
    ];
    public $jsOptions = [
        'appendTimestamp' => true,
    ];
}
