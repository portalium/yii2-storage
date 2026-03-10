<?php

namespace portalium\storage\models;

use portalium\helpers\FileHelper;
use portalium\storage\Module;
use Yii;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageShare;
use yii\web\UploadedFile;
use portalium\user\models\User;

/**
 * This is the model class for table "{{%storage_storage_directory}}".
 *
 * @property int $id_directory
 * @property int|null $id_parent
 * @property string $id_user
 * @property int|null $id_workspace
 * @property string $name
 * @property string $date_create
 * @property string $date_update
 *
 * @property StorageDirectory $parent
 * @property StorageDirectory[] $storageDirectories
 * @property Storage[] $storageStorages
 */
class StorageDirectory extends \yii\db\ActiveRecord
{
    public $type;

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'id_user',
                ],
                'value' => function () {
                    return Yii::$app->user->id;
                },
            ],
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'id_workspace',
                ],
                'value' => function () {
                    return Yii::$app->workspace->id ?? null;
                },
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%storage_storage_directory}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_parent', 'id_workspace'], 'default', 'value' => null],
            [['id_parent', 'id_workspace'], 'integer'],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_user' => 'id_user']],
            [['name'], 'required'],
            [['date_create', 'date_update', 'id_parent'], 'safe'],
            [['name'], 'string', 'max' => 256],
            [['id_parent'], 'exist', 'skipOnError' => true, 'targetClass' => StorageDirectory::class, 'targetAttribute' => ['id_parent' => 'id_directory']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_directory' => 'Id Directory',
            'id_parent' => 'Id Parent',
            'id_user' => Module::t('Id User'),
            'id_workspace' => Module::t('Workspace'),
            'name' => 'Name',
            'date_create' => 'Date Create',
            'date_update' => 'Date Update',
        ];
    }
    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(StorageDirectory::class, ['id_directory' => 'id_parent']);
    }

    /**
     * Gets query for [[StorageDirectories]].
     *
     * @return true
     */



    public function uploadFolder($uploadedFiles, $initialParentId = null)
    {
        if (empty($uploadedFiles)) {
            $this->addError('file', Module::t('No files were uploaded'));
            return false;
        }

        $userId = Yii::$app->user->id;
        $workspaceId = $this->id_workspace ?? 1;

        // All file types are now accepted - no extension filtering needed
        $validFiles = $uploadedFiles;

        $directories = [];
        $success = true;

        foreach ($validFiles as $file) {
            $fullPath = $file->fullPath ?? $file->name;
            $pathParts = explode('/', $fullPath);

            $fileName = array_pop($pathParts);
            $currentPath = '';
            $parentDirectoryId = $initialParentId;

            foreach ($pathParts as $depth => $folderName) {
                $currentPath = $depth === 0 ? $folderName : $currentPath . '/' . $folderName;

                if (!isset($directories[$currentPath])) {
                    $dir = new StorageDirectory();
                    $dir->name = $folderName;

                    if ($depth === 0 && $initialParentId !== null) {
                        $dir->id_parent = $initialParentId;
                    } else {
                        $dir->id_parent = $directories[dirname($currentPath)] ?? null;
                    }

                    if (!$dir->save()) {
                        foreach ($dir->errors as $attribute => $errors) {
                            foreach ($errors as $error) {
                                $this->addError($attribute, $error);
                            }
                        }
                        return false;
                    }
                    $directories[$currentPath] = $dir->id_directory;
                }
                $parentDirectoryId = $directories[$currentPath];
            }

            if (!empty($fileName)) {
                $storage = new Storage();
                $storage->title = pathinfo($fileName, PATHINFO_FILENAME);
                $storage->name = $fileName;
                $storage->id_directory = $parentDirectoryId;
                $storage->id_user = $userId;
                $storage->id_workspace = $workspaceId;
                $storage->file = $file;
                $storage->type = 'file';

                $mimeType = $storage->getMIMEType($fileName);
                $storage->mime_type = Storage::MIME_TYPE[$mimeType] ?? count(Storage::MIME_TYPE);
                $storage->hash_file = md5_file($file->tempName);
                if (!$storage->upload()) {
                    foreach ($storage->errors as $attribute => $errors) {
                        foreach ($errors as $error) {
                            $this->addError($attribute, $error);
                        }
                    }
                    $success = false;
                }
            }
        }

        return $success;
    }
    /**
     * Gets query for [[StorageDirectories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStorageDirectories()
    {
        return $this->hasMany(StorageDirectory::class, ['id_parent' => 'id_directory']);
    }

    /**
     * Gets query for [[StorageStorages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStorageStorages()
    {
        return $this->hasMany(Storage::class, ['id_directory' => 'id_directory']);
    }

    /**
     * Gets query for [[Shares]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShares()
    {
        return $this->hasMany(StorageShare::class, ['id_directory' => 'id_directory']);
    }

    /**
     * Gets active shares for this directory
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActiveShares()
    {
        return $this->getShares()
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
        $userId = Yii::$app->user->id;

        // Owner can always access
        if ($this->id_user == $userId) {
            return true;
        }

        // Check if shared with user (includes parent directories)
        if ($this->isSharedWith($userId, $requiredPermission)) {
            return true;
        }

        // Check workspace access (if directory has workspace field)
        if (
            isset($this->id_workspace) && $this->id_workspace &&
            Yii::$app->workspace->can('storage', 'storageWebDefaultIndex', ['model' => $this])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get all child items count (files + subdirectories) recursively
     *
     * @return int
     */
    public function getChildItemsCount()
    {
        $count = 0;

        // Count direct files
        $count += Storage::find()->where(['id_directory' => $this->id_directory])->count();

        // Count subdirectories and their contents recursively
        $subdirectories = StorageDirectory::find()->where(['id_parent' => $this->id_directory])->all();
        foreach ($subdirectories as $subdir) {
            $count++; // Count the subdirectory itself
            $count += $subdir->getChildItemsCount(); // Recursively count its contents
        }

        return $count;
    }
}
