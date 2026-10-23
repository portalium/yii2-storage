<?php

use portalium\db\Migration;
use portalium\site\Module as SiteModule;
use portalium\site\models\Form;
use portalium\storage\models\StorageProvider;

class m260916_000003_storage_provider_setting extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $table = SiteModule::$tablePrefix . 'setting';

        $this->delete($table, ['module' => 'storage', 'name' => 'storage::provider']);

        $this->insert($table, [
            'module' => 'storage',
            'name' => 'storage::provider',
            'label' => 'Storage Provider',
            'value' => StorageProvider::SETTING_SYSTEM,
            'type' => Form::TYPE_DROPDOWNLIST,
            'is_preference' => 1,
            'config' => json_encode([
                'method' => [
                    'class' => StorageProvider::class,
                    'name' => 'getSettingOptions',
                    'map' => [
                        'key' => 'id_provider',
                        'value' => 'name',
                    ],
                ],
                'hint' => 'Where your new uploads are stored. System follows the site default. Existing files keep their own provider.',
            ]),
        ]);
    }

    public function safeDown()
    {
        $table = SiteModule::$tablePrefix . 'setting';

        $this->delete($table, ['module' => 'storage', 'name' => 'storage::provider']);

        $this->insert($table, [
            'module' => 'storage',
            'name' => 'storage::provider',
            'label' => 'Storage Provider',
            'value' => 'local',
            'type' => Form::TYPE_DROPDOWNLIST,
            'is_preference' => 0,
            'config' => json_encode([
                'method' => [
                    'class' => \portalium\storage\providers\StorageProviderFactory::class,
                    'name' => 'getAvailable',
                    'map' => ['key' => 'name', 'value' => 'label'],
                ],
            ]),
        ]);
    }
}
