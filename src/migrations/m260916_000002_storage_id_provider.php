<?php

use portalium\db\Migration;
use portalium\storage\Module;
use portalium\storage\models\StorageProvider;
use portalium\site\Module as SiteModule;

class m260916_000002_storage_id_provider extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $storageTable = '{{%' . Module::$tablePrefix . 'storage}}';
        $providerTable = '{{%' . Module::$tablePrefix . 'provider}}';

        $schema = $this->db->getTableSchema($storageTable, true);
        if ($schema === null || $schema->getColumn('id_provider') === null) {
            $this->addColumn($storageTable, 'id_provider', $this->integer()->null()->after('disk'));
            $this->createIndex('idx_storage_storage_provider', $storageTable, 'id_provider');
            $this->addForeignKey(
                'fk_storage_storage_provider',
                $storageTable,
                'id_provider',
                $providerTable,
                'id_provider',
                'SET NULL',
                'CASCADE'
            );
        }

        $disks = $this->db->createCommand(
            "SELECT DISTINCT disk FROM {$storageTable} WHERE disk IS NOT NULL AND disk <> ''"
        )->queryColumn();

        if (!in_array('local', $disks, true)) {
            $disks[] = 'local';
        }

        $s3Config = $this->readLegacyS3Config();

        foreach ($disks as $disk) {
            $idProvider = $this->findOrCreateProviderForDisk($disk, $s3Config);

            if ($idProvider !== null) {
                $this->update($storageTable, ['id_provider' => $idProvider], ['disk' => $disk, 'id_provider' => null]);
            }
        }

        $localId = $this->db->createCommand(
            "SELECT id_provider FROM {$providerTable} WHERE type = 'local' AND id_workspace IS NULL LIMIT 1"
        )->queryScalar();

        if ($localId) {
            $this->update($storageTable, ['id_provider' => $localId], ['id_provider' => null]);
        }

        $this->removeLegacySettings();
    }

    public function safeDown()
    {
        $storageTable = '{{%' . Module::$tablePrefix . 'storage}}';

        $this->dropForeignKey('fk_storage_storage_provider', $storageTable);
        $this->dropIndex('idx_storage_storage_provider', $storageTable);
        $this->dropColumn($storageTable, 'id_provider');

        $this->delete('{{%' . Module::$tablePrefix . 'provider}}', ['id_workspace' => null]);
    }

    private function readLegacyS3Config(): array
    {
        $names = ['endpoint', 'region', 'bucket', 'key', 'secret', 'prefix'];
        $config = [];

        foreach ($names as $name) {
            $value = $this->db->createCommand(
                'SELECT value FROM ' . SiteModule::$tablePrefix . 'setting WHERE module = :m AND name = :n',
                [':m' => 'storage', ':n' => 'storage::s3_' . $name]
            )->queryScalar();

            $config[$name] = $value === false || $value === null ? '' : $value;
        }

        return $config;
    }

    private function findOrCreateProviderForDisk(string $disk, array $s3Config): ?int
    {
        $labels = [
            'local' => 'Local disk',
            'blaze' => 'Backblaze B2',
            's3' => 'AWS S3',
            'r2' => 'Cloudflare R2',
            'gcs' => 'Google Cloud Storage',
        ];
        $name = $labels[$disk] ?? ucfirst($disk);

        $providerTable = '{{%' . Module::$tablePrefix . 'provider}}';
        $existingId = $this->db->createCommand(
            "SELECT id_provider FROM {$providerTable} WHERE name = :name AND id_workspace IS NULL LIMIT 1",
            [':name' => $name]
        )->queryScalar();

        if ($existingId) {
            return (int) $existingId;
        }

        $provider = new StorageProvider();
        $provider->name = $name;
        $provider->type = $disk === 'local' ? StorageProvider::TYPE_LOCAL : StorageProvider::TYPE_S3;
        $provider->capability = StorageProvider::CAPABILITY_STORAGE;
        $provider->id_workspace = null;
        $provider->id_user = null;
        $provider->status = StorageProvider::STATUS_ACTIVE;

        if ($disk === 'local') {
            $provider->setConfigData([]);
        } else {
            $provider->setConfigData($this->isCurrentS3Disk($disk) ? $s3Config : []);
        }

        if (!$provider->save()) {
            throw new \RuntimeException('Could not create provider for disk "' . $disk . '": ' . json_encode($provider->getErrors()));
        }

        return (int) $provider->id_provider;
    }

    private function isCurrentS3Disk(string $disk): bool
    {
        $current = $this->db->createCommand(
            'SELECT value FROM ' . SiteModule::$tablePrefix . 'setting WHERE module = :m AND name = :n',
            [':m' => 'storage', ':n' => 'storage::provider']
        )->queryScalar();

        return $current === $disk;
    }

    private function removeLegacySettings(): void
    {
        $settingTable = SiteModule::$tablePrefix . 'setting';
        $providerTable = '{{%' . Module::$tablePrefix . 'provider}}';

        $current = $this->db->createCommand(
            "SELECT value FROM {$settingTable} WHERE module = 'storage' AND name = 'storage::provider'"
        )->queryScalar();

        $labels = [
            'local' => 'Local disk',
            'blaze' => 'Backblaze B2',
            's3' => 'AWS S3',
            'r2' => 'Cloudflare R2',
            'gcs' => 'Google Cloud Storage',
        ];

        $defaultName = $labels[$current] ?? 'Local disk';

        $this->update($providerTable, ['is_default' => 1], ['name' => $defaultName, 'id_workspace' => null]);

        foreach (['endpoint', 'region', 'bucket', 'key', 'secret', 'prefix'] as $name) {
            $this->delete($settingTable, ['module' => 'storage', 'name' => 'storage::s3_' . $name]);
        }
    }
}
