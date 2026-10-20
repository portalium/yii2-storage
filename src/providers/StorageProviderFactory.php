<?php

namespace portalium\storage\providers;

use portalium\storage\models\StorageProvider;
use Yii;

/**
 * Resolves storage providers.
 *
 * Two distinct questions are answered here, and mixing them up is the easiest
 * way to break existing files:
 *
 *  - Where should a NEW file be written?  → getDefault(), driven by the
 *    `storage::provider` setting.
 *  - Where does an EXISTING file live?    → forDisk(), driven by the record's
 *    own `disk` column.
 *
 * Reading must always follow the record, never the current setting, so that
 * switching the default provider never breaks files already stored elsewhere.
 */
class StorageProviderFactory
{
    /**
     * @var StorageProviderInterface[] Instances keyed by provider name.
     */
    private static $instances = [];

    /**
     * @var StorageProviderInterface[] Instances keyed by provider record id.
     */
    private static $byRecord = [];

    /**
     * Provider used for new writes.
     *
     * The setting is a per-user preference holding a provider id, so two people
     * in the same workspace can upload to different accounts while still seeing
     * each other's files.
     */
    public static function getDefault(): StorageProviderInterface
    {
        $value = (string) Yii::$app->setting->getValue('storage::provider');

        // "system" follows whatever the administrator has set as the site
        // default, rather than pinning the user to one account.
        if ($value === StorageProvider::SETTING_SYSTEM) {
            $record = StorageProvider::findDefault();

            if ($record !== null) {
                return self::fromRecord($record);
            }

            return self::get(LocalProvider::NAME);
        }

        if ($value !== '' && ctype_digit($value)) {
            // The preference is user-supplied, so it is re-checked here rather
            // than trusted: the dropdown filters the list, but nothing stops a
            // crafted request from naming another workspace's provider.
            $record = self::findPermitted((int) $value);

            if ($record !== null) {
                return self::fromRecord($record);
            }
        }

        // Preference missing, or pointing at a provider that is gone or no
        // longer permitted: fall back to the system default.
        $record = StorageProvider::findDefault();

        if ($record !== null) {
            return self::fromRecord($record);
        }

        // Legacy path, before the provider table existed.
        return self::get($value !== '' ? $value : LocalProvider::NAME);
    }

    /**
     * The provider record a write may target, or null when it may not.
     *
     * A provider is only a valid destination when it is active, stores files
     * (import sources such as Drive are not destinations) and belongs either to
     * the whole site or to the caller's active workspace.
     */
    private static function findPermitted(int $idProvider): ?StorageProvider
    {
        $record = StorageProvider::findOne($idProvider);

        if ($record === null) {
            return null;
        }

        if (!$record->isActive()) {
            return null;
        }

        if ($record->capability !== StorageProvider::CAPABILITY_STORAGE) {
            return null;
        }

        if ($record->id_workspace === null) {
            return $record;
        }

        $idWorkspace = null;

        try {
            $idWorkspace = Yii::$app->workspace->id;
        } catch (\Throwable $e) {
            // No workspace context (console): only system providers qualify.
        }

        return ((int) $record->id_workspace === (int) $idWorkspace) ? $record : null;
    }

    /**
     * Provider for a record id, or null when it is missing or not permitted.
     */
    public static function forProvider($idProvider): ?StorageProviderInterface
    {
        $idProvider = (int) $idProvider;

        if ($idProvider <= 0) {
            return null;
        }

        if (isset(self::$byRecord[$idProvider])) {
            return self::$byRecord[$idProvider];
        }

        $record = StorageProvider::findOne($idProvider);

        if ($record === null) {
            return null;
        }

        return self::$byRecord[$idProvider] = self::fromRecord($record);
    }

    /**
     * Build a provider from its record.
     */
    public static function fromRecord(StorageProvider $record): StorageProviderInterface
    {
        if ($record->type === StorageProvider::TYPE_LOCAL) {
            return new LocalProvider((int) $record->id_provider);
        }

        return new S3Provider((string) $record->id_provider, $record);
    }

    /**
     * Provider that owns an existing record's bytes.
     *
     * Records written before the provider layer have no `disk` value; they are
     * local by definition.
     */
    public static function forDisk(?string $disk): StorageProviderInterface
    {
        return self::get(($disk === null || $disk === '') ? LocalProvider::NAME : $disk);
    }

    /**
     * Provider for a Storage record, falling back to local for legacy rows.
     *
     * @param \portalium\storage\models\Storage|null $model
     */
    public static function forModel($model): StorageProviderInterface
    {
        if ($model === null) {
            return self::forDisk(null);
        }

        // A file always reads from the provider that holds its bytes, never from
        // whatever the current default happens to be.
        if (!empty($model->id_provider)) {
            $provider = self::forProvider($model->id_provider);

            if ($provider !== null) {
                return $provider;
            }
        }

        return self::forDisk($model->disk ?? null);
    }

    /**
     * @param string $name Provider identifier, e.g. "local" or "blaze".
     */
    public static function get(string $name): StorageProviderInterface
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        if (in_array($name, S3Provider::NAMES, true)) {
            // One class serves every S3-compatible service; the name is carried
            // through so storage.disk records which one holds the bytes.
            $provider = new S3Provider($name);
        } else {
            $provider = new LocalProvider();
        }

        return self::$instances[$name] = $provider;
    }

    /**
     * Providers offered in the settings dropdown.
     *
     * Shaped for the setting form's `method` config, which maps rows through
     * ArrayHelper::map() using the configured key/value fields.
     *
     * @param array $params Unused; the setting form always passes an array.
     * @return array<int,array{name:string,label:string}>
     */
    public static function getAvailable($params = []): array
    {
        return [
            ['name' => LocalProvider::NAME, 'label' => 'Local disk'],
            ['name' => S3Provider::NAME, 'label' => 'Backblaze B2'],
            ['name' => S3Provider::NAME_S3, 'label' => 'AWS S3'],
            ['name' => S3Provider::NAME_R2, 'label' => 'Cloudflare R2'],
            ['name' => S3Provider::NAME_GCS, 'label' => 'Google Cloud Storage'],
        ];
    }

    /**
     * Drops cached instances so changed settings take effect.
     */
    public static function flush(): void
    {
        self::$instances = [];
        self::$byRecord = [];
    }
}
