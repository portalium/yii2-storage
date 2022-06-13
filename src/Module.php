<?php

namespace diginova\storage;

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
    public static function moduleInit()
    {
        self::registerTranslation('storage','@diginova/storage/messages',[
            'storage' => 'storage.php',
        ]);
    }

    public static function t($message, array $params = [])
    {
        return parent::coreT('storage', $message, $params);
    }
}