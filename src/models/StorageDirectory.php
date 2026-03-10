<?php

namespace portalium\storage\models;

use Yii;

/**
 * StorageDirectory — backward-compatibility wrapper.
 *
 * After the directory-merge migration, directories live in the same
 * `storage_storage` table with `type = 'directory'`.
 *
 * This class extends Storage and adds a default scope so that queries
 * built through StorageDirectory automatically filter to directories.
 *
 * Legacy properties are mapped:
 *   id_directory  → id_storage
 *   id_parent     → id_directory  (self-referencing in storage table)
 *   name          → name / title
 *
 * @deprecated Use Storage model with type=directory directly.
 *
 * @property int $id_directory Alias for id_storage
 * @property int|null $id_parent Alias for id_directory
 */
class StorageDirectory extends Storage
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        // Auto-set type for new records
        if ($this->isNewRecord) {
            $this->type = self::TYPE_DIRECTORY;
            $this->mime_type = 0;
        }
    }

    /**
     * {@inheritdoc}
     * Default scope: only directories.
     */
    public static function find()
    {
        return parent::find()->andWhere(['type' => self::TYPE_DIRECTORY]);
    }

    // ─── Legacy property aliases ────────────────────────────

    /**
     * Get id_directory (alias for id_storage)
     * @return int|null
     */
    public function getIdDirectory()
    {
        return $this->id_storage;
    }

    /**
     * Get id_parent (alias for id_directory / parent storage id)
     * @return int|null
     */
    public function getId_parent()
    {
        return $this->getAttribute('id_directory');
    }

    /**
     * Set id_parent (alias for id_directory)
     * @param int|null $value
     */
    public function setId_parent($value)
    {
        $this->setAttribute('id_directory', $value);
    }

    // ─── Legacy relation aliases ────────────────────────────

    /**
     * Gets query for [[Parent]].
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::class, ['id_storage' => 'id_directory']);
    }

    /**
     * Gets query for subdirectories.
     * @return \yii\db\ActiveQuery
     */
    public function getStorageDirectories()
    {
        return $this->hasMany(self::class, ['id_directory' => 'id_storage'])
            ->andWhere(['type' => self::TYPE_DIRECTORY]);
    }

    /**
     * Gets query for files in this directory.
     * @return \yii\db\ActiveQuery
     */
    public function getStorageStorages()
    {
        return $this->hasMany(Storage::class, ['id_directory' => 'id_storage'])
            ->andWhere(['type' => self::TYPE_FILE]);
    }

    /**
     * Gets query for [[Shares]].
     * @return \yii\db\ActiveQuery
     */
    public function getDirectoryShares()
    {
        return $this->hasMany(StorageShare::class, ['id_directory' => 'id_storage']);
    }

    /**
     * Gets active shares for this directory
     * @return \yii\db\ActiveQuery
     */
    public function getActiveDirectoryShares()
    {
        return $this->getDirectoryShares()
            ->where(['is_active' => 1])
            ->andWhere([
                'OR',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')]
            ]);
    }

    /**
     * Check if directory is shared with a specific user
     * Also checks parent directories (hierarchical)
     *
     * @param int $id_user User ID
     * @param string $requiredPermission Required permission level
     * @return bool
     */
    public function isSharedWith($id_user, $requiredPermission = StorageShare::PERMISSION_VIEW)
    {
        return StorageShare::hasAccess($id_user, null, $this, $requiredPermission);
    }

    /**
     * Check if current user can access this directory
     * Considers: ownership, shares (including parent), workspace membership
     *
     * @param string $requiredPermission Required permission level
     * @return bool
     */
    public function canAccess($requiredPermission = StorageShare::PERMISSION_VIEW)
    {
        return $this->canAccessDirectory($requiredPermission);
    }

    /**
     * Upload folder — delegate to Storage::uploadFolder()
     */
    public function uploadFolder($uploadedFiles, $initialParentId = null)
    {
        $storage = new Storage();
        $storage->type = self::TYPE_DIRECTORY;
        $storage->id_workspace = $this->id_workspace ?? (Yii::$app->workspace->id ?? 1);
        return $storage->uploadFolder($uploadedFiles, $initialParentId);
    }
}
