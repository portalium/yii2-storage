<?php

namespace portalium\storage;
use portalium\base\Event;
use portalium\storage\components\TriggerActions;

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
    public static $supportWorkspace = true;
    public static function moduleInit()
    {
        self::registerTranslation('storage','@portalium/storage/messages',[
            'storage' => 'storage.php',
        ]);
    }

    public function getMenuItems()
    {
        $menuItems = [
            [
                [
                    'menu' => 'web',
                    'type' => 'action',
                    'route' => '/storage/default/index',
                ]
            ],
        ];
        return $menuItems;
    }

    public static function t($message, array $params = [])
    {
        return parent::coreT('storage', $message, $params);
    }

    public function registerEvents()
    {
        Event::on($this::className(), \portalium\workspace\Module::EVENT_ROLE_UPDATE_AFTER, [new TriggerActions(), 'onWorkspaceAvailableRoleUpdateAfter']);

        Event::on($this::className(), \portalium\workspace\Module::EVENT_USER_CREATE_AFTER, [new TriggerActions(), 'onWorkspaceUserCreateAfter']);
    }
}