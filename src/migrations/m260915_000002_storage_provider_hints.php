<?php

use portalium\db\Migration;
use portalium\site\Module as SiteModule;

class m260915_000002_storage_provider_hints extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        foreach ($this->hints() as $name => $config) {
            $this->update(
                SiteModule::$tablePrefix . 'setting',
                ['config' => json_encode($config)],
                ['module' => 'storage', 'name' => $name]
            );
        }
    }

    public function safeDown()
    {
        foreach ($this->oldHints() as $name => $config) {
            $this->update(
                SiteModule::$tablePrefix . 'setting',
                ['config' => json_encode($config)],
                ['module' => 'storage', 'name' => $name]
            );
        }
    }

    private function hints()
    {
        return [
            'storage::s3_endpoint' => [
                'hint' => 'B2: https://s3.<region>.backblazeb2.com | AWS: https://s3.<region>.amazonaws.com | R2: https://<account-id>.r2.cloudflarestorage.com | GCS: https://storage.googleapis.com',
                'placeholder' => 'https://s3.us-west-004.backblazeb2.com',
            ],
            'storage::s3_region' => [
                'hint' => 'Must match the endpoint exactly. R2 and GCS use: auto',
                'placeholder' => 'us-west-004',
            ],
            'storage::s3_key' => [
                'hint' => 'B2: keyID | AWS: Access Key ID | R2: Access Key ID | GCS: HMAC Access ID',
                'placeholder' => '',
            ],
            'storage::s3_secret' => [
                'hint' => 'B2: applicationKey | AWS/R2: Secret Access Key | GCS: HMAC Secret. Leave blank to keep the current value.',
                'placeholder' => '',
            ],
        ];
    }

    private function oldHints()
    {
        return [
            'storage::s3_endpoint' => [
                'hint' => 'Backblaze B2 example: https://s3.us-west-002.backblazeb2.com',
                'placeholder' => 'https://s3.us-west-002.backblazeb2.com',
            ],
            'storage::s3_region' => [
                'hint' => 'Must match the endpoint, e.g. us-west-002',
                'placeholder' => 'us-west-002',
            ],
            'storage::s3_key' => [
                'hint' => 'Backblaze application keyID.',
                'placeholder' => '',
            ],
            'storage::s3_secret' => [
                'hint' => 'Backblaze applicationKey. Leave blank to keep the current value.',
                'placeholder' => '',
            ],
        ];
    }
}
