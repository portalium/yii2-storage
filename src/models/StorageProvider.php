<?php

namespace portalium\storage\models;

use Yii;
use portalium\storage\Module;
use portalium\user\models\User;
use portalium\workspace\models\Workspace;

/**
 * A configured storage backend or import source.
 *
 * Providers are data rather than settings, so several accounts of the same kind
 * can coexist: a file records which provider holds its bytes, and reading always
 * follows that record. Switching the selected provider therefore never breaks
 * files already stored elsewhere.
 *
 * @property int $id_provider
 * @property string $name
 * @property string $type local|s3|gdrive|dropbox
 * @property string $capability storage|source
 * @property string|null $config Encrypted credentials
 * @property int|null $id_user Creator, kept for audit only
 * @property int|null $id_workspace NULL means system-wide
 * @property int $status
 * @property int $is_default
 * @property string $date_create
 * @property string|null $date_update
 */
class StorageProvider extends \yii\db\ActiveRecord
{
    const TYPE_LOCAL = 'local';
    const TYPE_S3 = 's3';
    const TYPE_GDRIVE = 'gdrive';
    const TYPE_DROPBOX = 'dropbox';

    /** Writable destination: appears in the settings dropdown. */
    const CAPABILITY_STORAGE = 'storage';

    /** Import-only source (Drive, Dropbox): never a storage destination. */
    const CAPABILITY_SOURCE = 'source';

    const STATUS_ACTIVE = 1;
    const STATUS_PASSIVE = 0;

    /**
     * @var string|null Form-only value: "system" or "workspace".
     *
     * Not a column; the controller turns it into id_workspace after checking
     * the permission, so a posted value alone cannot widen a provider's reach.
     */
    public $scope;

    /**
     * @var array|null Decoded config, cached per instance.
     */
    private $decodedConfig;

    public static function tableName()
    {
        return '{{%' . Module::$tablePrefix . 'provider}}';
    }

    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            [['name'], 'string', 'max' => 64],
            [['type'], 'string', 'max' => 32],
            [['type'], 'in', 'range' => array_keys(self::getTypes())],
            [['capability'], 'in', 'range' => [self::CAPABILITY_STORAGE, self::CAPABILITY_SOURCE]],
            [['capability'], 'default', 'value' => self::CAPABILITY_STORAGE],
            [['config'], 'safe'],
            [['id_user', 'id_workspace'], 'integer'],
            [['status', 'is_default'], 'integer'],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['is_default'], 'default', 'value' => 0],
            [['date_create', 'date_update'], 'safe'],
            [['scope'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_provider' => Module::t('ID'),
            'name' => Module::t('Name'),
            'type' => Module::t('Type'),
            'capability' => Module::t('Capability'),
            'id_workspace' => Module::t('Workspace'),
            'status' => Module::t('Status'),
            'is_default' => Module::t('Default'),
            'scope' => Module::t('Scope'),
            'date_create' => Module::t('Created'),
            'date_update' => Module::t('Updated'),
        ];
    }

    /**
     * Credentials never leave the server through API output.
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['config']);

        return $fields;
    }

    public static function getTypes()
    {
        return [
            self::TYPE_LOCAL => Module::t('Local disk'),
            self::TYPE_S3 => Module::t('S3 compatible'),
            self::TYPE_GDRIVE => Module::t('Google Drive'),
            self::TYPE_DROPBOX => Module::t('Dropbox'),
        ];
    }

    /**
     * Which capability each type can serve.
     *
     * Drive and Dropbox are import sources, never storage destinations: their
     * tokens expire and their quotas belong to the end user, so production
     * uploads must not depend on them.
     */
    public static function getCapabilityForType($type)
    {
        return in_array($type, [self::TYPE_GDRIVE, self::TYPE_DROPBOX], true)
            ? self::CAPABILITY_SOURCE
            : self::CAPABILITY_STORAGE;
    }

    public function getTypeLabel()
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function isSystem()
    {
        return $this->id_workspace === null;
    }

    public function isActive()
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Config: stored encrypted, never written to the database in clear text
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Decoded configuration values.
     */
    public function getConfigData(): array
    {
        if ($this->decodedConfig === null) {
            $this->decodedConfig = self::decodeConfig($this->config);
        }

        return $this->decodedConfig;
    }

    public function getConfigValue(string $key, $default = null)
    {
        return $this->getConfigData()[$key] ?? $default;
    }

    /**
     * Replace the whole configuration.
     */
    public function setConfigData(array $data): void
    {
        $this->decodedConfig = $data;
        $this->config = self::encodeConfig($data);
    }

    /**
     * Merge values in, keeping existing keys that are not supplied.
     *
     * Used by the form so a blank secret field keeps the stored secret instead
     * of wiping it.
     */
    public function mergeConfigData(array $data): void
    {
        $this->setConfigData(array_merge($this->getConfigData(), $data));
    }

    private static function encodeConfig(array $data): string
    {
        $json = json_encode($data);

        return base64_encode(Yii::$app->security->encryptByKey($json, self::encryptionKey()));
    }

    private static function decodeConfig($stored): array
    {
        if ($stored === null || $stored === '') {
            return [];
        }

        $raw = base64_decode($stored, true);

        if ($raw === false) {
            return [];
        }

        try {
            $json = Yii::$app->security->decryptByKey($raw, self::encryptionKey());
        } catch (\Throwable $e) {
            Yii::error('Provider config could not be decrypted: ' . $e->getMessage(), __METHOD__);

            return [];
        }

        if ($json === false) {
            return [];
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Key used to encrypt provider credentials.
     *
     * Interim decision (2026-09-15): the key lives in the database, because
     * main-local.php must not be touched. That means an attacker holding a full
     * database dump could decrypt these values; what encryption still buys is
     * that credentials never appear in the settings screen, in logs or in API
     * output. Moving the key out of the database is pending a decision.
     */
    private static function encryptionKey(): string
    {
        $key = Yii::$app->setting->getValue('storage::provider_key');

        if (empty($key)) {
            throw new \yii\base\InvalidConfigException(
                'storage::provider_key setting is missing. Run the storage provider migrations.'
            );
        }

        return $key;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Queries
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Providers the given workspace may use: its own plus system-wide ones.
     */
    public static function findAvailable($idWorkspace = null, $capability = self::CAPABILITY_STORAGE)
    {
        $query = self::find()
            ->where(['status' => self::STATUS_ACTIVE, 'capability' => $capability]);

        if ($idWorkspace === null) {
            $query->andWhere(['id_workspace' => null]);
        } else {
            $query->andWhere(['or', ['id_workspace' => null], ['id_workspace' => $idWorkspace]]);
        }

        return $query->orderBy(['is_default' => SORT_DESC, 'name' => SORT_ASC]);
    }

    /**
     * Options for the settings dropdown.
     *
     * Only storage-capable providers appear here: Drive and Dropbox are import
     * sources, so offering them as an upload destination would be wrong.
     * The list is scoped to the active workspace plus system-wide providers.
     *
     * @param array $params Unused; the setting form always passes an array.
     * @return array<int,array{id_provider:int,name:string}>
     */
    /**
     * Value meaning "whatever the administrator has chosen for the site".
     *
     * Stored instead of a provider id so the choice keeps following the system
     * default: if the administrator later points it at another account, users
     * on this setting move with it and nothing has to be re-saved.
     */
    const SETTING_SYSTEM = 'system';

    public static function getSettingOptions($params = []): array
    {
        $idWorkspace = null;

        try {
            $idWorkspace = Yii::$app->workspace->id;
        } catch (\Throwable $e) {
            // Console context has no workspace; the system option still applies.
        }

        // The site's own providers are collapsed into one entry: which account
        // sits behind them is an operational detail users have no say over.
        $options = [[
            'id_provider' => self::SETTING_SYSTEM,
            'name' => Module::t('System'),
        ]];

        if ($idWorkspace === null) {
            return $options;
        }

        $rows = self::find()
            ->where([
                'status' => self::STATUS_ACTIVE,
                'capability' => self::CAPABILITY_STORAGE,
                'id_workspace' => $idWorkspace,
            ])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($rows as $provider) {
            $options[] = [
                'id_provider' => $provider->id_provider,
                'name' => $provider->name,
            ];
        }

        return $options;
    }

    /**
     * System providers offered in the site-wide setting.
     *
     * @param array $params Unused; the setting form always passes an array.
     * @return array<int,array{id_provider:int,name:string}>
     */
    public static function getSystemOptions($params = []): array
    {
        $rows = self::find()
            ->where([
                'id_workspace' => null,
                'capability' => self::CAPABILITY_STORAGE,
                'status' => self::STATUS_ACTIVE,
            ])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return array_map(function (self $provider) {
            return [
                'id_provider' => $provider->id_provider,
                'name' => $provider->name,
            ];
        }, $rows);
    }

    /**
     * Destination for users who selected System.
     *
     * Read from the site setting rather than a flag on the record, so choosing
     * it is one administrative decision in one place instead of a checkbox that
     * has to be unticked somewhere else first.
     */
    public static function findDefault()
    {
        $id = null;

        try {
            $id = Yii::$app->setting->getValue('storage::system_provider');
        } catch (\Throwable $e) {
            // Setting missing (not migrated yet): fall through to the flag.
        }

        if (!empty($id) && ctype_digit((string) $id)) {
            $record = self::findOne((int) $id);

            if (
                $record !== null
                && $record->id_workspace === null
                && $record->isActive()
                && $record->capability === self::CAPABILITY_STORAGE
            ) {
                return $record;
            }
        }

        // Installations that have not run the setting migration yet.
        return self::find()
            ->where([
                'id_workspace' => null,
                'capability' => self::CAPABILITY_STORAGE,
                'status' => self::STATUS_ACTIVE,
                'is_default' => 1,
            ])
            ->one();
    }

    /**
     * How many files still point at this provider.
     */
    public function getFileCount(): int
    {
        return (int) Storage::find()
            ->where(['id_provider' => $this->id_provider, 'type' => Storage::TYPE_FILE])
            ->count();
    }

    /**
     * A provider holding files is never deleted, only deactivated, so that the
     * bytes it points at stay reachable.
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->getFileCount() > 0) {
            $this->addError('id_provider', Module::t('This provider still holds files and cannot be deleted. Deactivate it instead.'));

            return false;
        }

        return true;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Only one system default may exist at a time.
        if ($this->is_default && $this->id_workspace === null) {
            self::updateAll(['is_default' => 0], ['and', ['id_workspace' => null], ['!=', 'id_provider', (int) $this->id_provider]]);
        }

        return true;
    }

    public function getWorkspace()
    {
        return $this->hasOne(Workspace::class, ['id_workspace' => 'id_workspace']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id_user' => 'id_user']);
    }
}
