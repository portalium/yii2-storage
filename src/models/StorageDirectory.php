<?php

namespace portalium\storage\models;

use portalium\helpers\FileHelper;
use portalium\storage\Module;
use Yii;
use portalium\storage\models\Storage;
use yii\web\UploadedFile;

/**
 * This is the model class for table "{{%storage_storage_directory}}".
 *
 * @property int $id_directory
 * @property int|null $id_parent
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
            [['id_parent'], 'default', 'value' => null],
            [['id_parent'], 'integer'],
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
        $workspaceId = $this->id_workspace ?? 0;
        $allowed = Storage::$allowExtensions;
        $validFiles = [];
        foreach ($uploadedFiles as $file) {
            $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $validFiles[] = $file;
            }
        }
        if (empty($validFiles)) {
            $this->addError('file', Module::t('No valid files to upload.'));
            return false;
        }
        $directories = [];
        $success = true;
        foreach ($validFiles as $file) {
            $fullPath = $file->fullPath ?? $file->name;
            $pathParts = explode('/', $fullPath);
            $fileName = array_pop($pathParts);
            $currentPath = '';
            $parentDirectoryId = $initialParentId;
            $rootFolderName = !empty($pathParts) ? $pathParts[0] : null;
            if ($rootFolderName === $this->name && !empty($pathParts)) {
                $directories[$rootFolderName] = $this->id_directory;
                $parentDirectoryId = $this->id_directory;
                $currentPath = $rootFolderName;
                array_shift($pathParts);
            }
            foreach ($pathParts as $depth => $folderName) {
                $currentPath = $currentPath === '' ? $folderName : $currentPath . '/' . $folderName;
                if (!isset($directories[$currentPath])) {
                    $dir = new StorageDirectory();
                    $baseName = $folderName;
                    $name = $baseName;
                    $counter = 1;
                    $parentId = null;
                    if ($depth === 0 && $initialParentId !== null && empty($directories)) {
                        $parentId = $initialParentId;
                    } else {
                        $parentId = $directories[dirname($currentPath)] ?? null;
                    }
                    while (StorageDirectory::find()
                        ->where(['name' => $name, 'id_parent' => $parentId])
                        ->exists()) {
                        $name = $baseName . ' (' . $counter . ')';
                        $counter++;
                    }
                    $dir->name = $name;
                    $dir->id_parent = $parentId;
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
                $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                $name = $baseName;
                $counter = 1;
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                while (Storage::find()
                    ->where(['title' => $name, 'id_directory' => $parentDirectoryId])
                    ->exists()) {
                    $name = $baseName . ' (' . $counter . ')';
                    $counter++;
                }
                $storage->title = $name;
                $storage->name = !empty($extension) ? $name . '.' . $extension : $name;
                $storage->id_directory = $parentDirectoryId;
                $storage->id_user = $userId;
                $storage->id_workspace = $workspaceId;
                $storage->file = $file;
                $storage->type = 'file';

                $mimeType = $storage->getMIMEType($fileName);
                $storage->mime_type = Storage::MIME_TYPE[$mimeType] ?? count(Storage::MIME_TYPE);

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

}