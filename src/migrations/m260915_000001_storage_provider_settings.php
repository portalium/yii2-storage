<?php

use portalium\db\Migration;
use portalium\site\Module as SiteModule;
use portalium\site\models\Form;

class m260915_000001_storage_provider_settings extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $table = SiteModule::$tablePrefix . 'setting';

        if ($this->settingExists($table, 'storage::provider')) {
            return true;
        }

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
                    'map' => [
                        'key' => 'name',
                        'value' => 'label',
                    ],
                ],
                'hint' => 'Where new files are written. Existing files keep their own provider.',
            ]),
        ]);

        $textSettings = [
            [
                'name' => 'storage::s3_endpoint',
                'label' => 'S3 Endpoint',
                'hint' => 'Backblaze B2 example: https://s3.us-west-002.backblazeb2.com',
                'placeholder' => 'https://s3.us-west-002.backblazeb2.com',
            ],
            [
                'name' => 'storage::s3_region',
                'label' => 'S3 Region',
                'hint' => 'Must match the endpoint, e.g. us-west-002',
                'placeholder' => 'us-west-002',
            ],
            [
                'name' => 'storage::s3_bucket',
                'label' => 'S3 Bucket',
                'hint' => 'Use a private bucket.',
                'placeholder' => 'my-bucket',
            ],
            [
                'name' => 'storage::s3_key',
                'label' => 'S3 Access Key ID',
                'hint' => 'Backblaze application keyID.',
                'placeholder' => '',
            ],
            [
                'name' => 'storage::s3_prefix',
                'label' => 'S3 Key Prefix',
                'hint' => 'Optional folder prefix inside the bucket, e.g. alfa',
                'placeholder' => '',
            ],
        ];

        foreach ($textSettings as $setting) {
            $this->insert($table, [
                'module' => 'storage',
                'name' => $setting['name'],
                'label' => $setting['label'],
                'value' => '',
                'type' => Form::TYPE_INPUTTEXT,
                'is_preference' => 0,
                'config' => json_encode([
                    'hint' => $setting['hint'],
                    'placeholder' => $setting['placeholder'],
                ]),
            ]);
        }

        $this->insert($table, [
            'module' => 'storage',
            'name' => 'storage::s3_secret',
            'label' => 'S3 Secret Key',
            'value' => '',
            'type' => Form::TYPE_INPUTPASSWORD,
            'is_preference' => 0,
            'config' => json_encode([
                'hint' => 'Backblaze applicationKey. Leave blank to keep the current value.',
                'placeholder' => '',
            ]),
        ]);
    }

    private function settingExists($table, $name)
    {
        return (new \yii\db\Query())
            ->select('name')
            ->from($table)
            ->where(['module' => 'storage', 'name' => $name])
            ->createCommand($this->db)
            ->queryScalar() !== false;
    }

    public function safeDown()
    {
        $table = SiteModule::$tablePrefix . 'setting';

        $names = [
            'storage::provider',
            'storage::s3_endpoint',
            'storage::s3_region',
            'storage::s3_bucket',
            'storage::s3_key',
            'storage::s3_prefix',
            'storage::s3_secret',
        ];

        foreach ($names as $name) {
            $this->delete($table, ['module' => 'storage', 'name' => $name]);
        }
    }
}
