<?php

namespace portalium\storage;

class Module extends \portalium\base\Module
{
    public $apiRules = [
        [
            'class' => 'yii\rest\UrlRule',
            'controller' => [
                'storage/default',
            ]
        ],
    ];
    public static $tablePrefix = 'storage_';

    public static $name = 'Storage';
    
    public static function moduleInit()
    {
        self::registerTranslation('storage','@portalium/storage/messages',[
            'storage' => 'storage.php',
        ]);
    }

    public static function t($message, array $params = [])
    {
        return parent::coreT('storage', $message, $params);
    }
}