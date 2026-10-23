<?php

use portalium\db\Migration;
use portalium\site\Module as SiteModule;
use portalium\site\models\Form;
use portalium\storage\models\StorageProvider;

class m260916_000008_storage_system_provider_setting extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $table = SiteModule::$tablePrefix . 'setting';

        $current = $this->db->createCommand(
            "SELECT id_provider FROM {{%storage_provider}} WHERE is_default = 1 AND id_workspace IS NULL LIMIT 1"
        )->queryScalar();

        $this->delete($table, ['module' => 'storage', 'name' => 'storage::system_provider']);

        $this->insert($table, [
            'module' => 'storage',
            'name' => 'storage::system_provider',
            'label' => 'System Storage Provider',
            'value' => $current ?: '',
            'type' => Form::TYPE_DROPDOWNLIST,
            'is_preference' => 0,
            'config' => json_encode([
                'method' => [
                    'class' => StorageProvider::class,
                    'name' => 'getSystemOptions',
                    'map' => [
                        'key' => 'id_provider',
                        'value' => 'name',
                    ],
                ],
                'hint' => 'Destination for users who selected System. Applies site-wide.',
            ]),
        ]);
    }

    public function safeDown()
    {
        $this->delete(SiteModule::$tablePrefix . 'setting', [
            'module' => 'storage',
            'name' => 'storage::system_provider',
        ]);
    }
}
