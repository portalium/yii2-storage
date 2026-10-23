<?php

namespace portalium\storage\models;

use portalium\workspace\models\WorkspaceUser;
use Yii;
use portalium\storage\Module;
use yii\helpers\ArrayHelper;
use portalium\user\models\User;
use portalium\base\Event;
use portalium\storage\models\StorageShare;
use portalium\storage\providers\StorageProviderFactory;
use portalium\storage\models\StorageProvider;

/**
 * This is the model class for table "{{%storage_storage}}".
 *
 * @property int $id_storage
 * @property string $type file|directory
 * @property string $name
 * @property string $title
 * @property string $id_user
 * @property string $mime_type
 * @property string $id_workspace
 * @property string $access
 * @property string $hash_file
 * @property int|null $id_directory Parent directory (self-referencing: points to another Storage row with type=directory)
 * @property string|null $thumbnail
 * @property string|null $date_last_access
 * @property int $access_count
 * @property string $date_create
 * @property string $date_update
 *
 * @property Storage|null $parentDirectory
 * @property Storage[] $children  Child items (files + subdirectories) inside this directory
 * @property Storage[] $childDirectories  Subdirectories only
 * @property Storage[] $childFiles  Files only
 */
class Storage extends \yii\db\ActiveRecord
{
    public $file;
    public $allowedExtensions;

    /**
     * Top-level directory created (or reused) by the last folder upload.
     */
    public $rootDirectoryId;

    const TYPE_FILE = 'file';
    const TYPE_DIRECTORY = 'directory';

    const ACCESS_PUBLIC = 1;
    const ACCESS_PRIVATE = 0;

    const IS_MODAL_TRUE = 1;
    const IS_MODAL_FALSE = 0;
    
    const MIME_TYPE = [
        'image/jpeg' => '0',
        'image/png' => '1',
        'application/pdf' => '2',
        'application/msword' => '3',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '4',
        'application/vnd.ms-excel' => '5',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '6',
        'application/vnd.ms-powerpoint' => '7',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '8',
        'video/mp4' => '9',
        'audio/mpeg' => '10',
        'video/x-msvideo' => '11',
        'video/quicktime' => '12',
        'video/x-matroska' => '13',
        'application/zip' => '14',
        'application/x-rar-compressed' => '15',
        'application/x-7z-compressed' => '16',
        'image/jpg' => '17',
        'application/gzip' => '18',
        'text/plain' => '19',
        'text/csv' => '20',
        'text/html' => '21',
        'text/xml' => '22',
        'application/json' => '23',
        'application/x-tar' => '24',
        'image/svg+xml' => '25',
        'application/pb'=>'26',
        'application/onnx'=>'27',
        'application/weights'=>'28',
        'application/prototxt'=>'29',
        'application/cfg'=>'30',
        'application/pbtxt'=>'31',
        'application/pth' => '32',
        'application/vnd.caffemodel+json' => '33',
        'other' => '99',
    ];


    // Removed $allowExtensions - all file types are now accepted

    const THUMBNAIL_MAX_SIZE_KB = 40;
    const THUMBNAIL_DEFAULT_HEIGHT = 200;
    const THUMBNAIL_ALLOWED_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    /**
     * Provider that owns this record's bytes.
     *
     * Always resolved from the record's own `disk` value, never from the
     * current default setting, so switching providers never breaks old files.
     */
    public function getProvider()
    {
        return StorageProviderFactory::forModel($this);
    }

    /**
     * Gets query for [[Provider]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProviderRecord()
    {
        return $this->hasOne(StorageProvider::class, ['id_provider' => 'id_provider']);
    }

    /**
     * Materialise this record as a local file path.
     *
     * Remote providers download to a temp file; $isTemporary then tells the
     * caller it must unlink the path when finished.
     *
     * @return string|false
     */
    public function getLocalPath(?bool &$isTemporary = null)
    {
        return $this->getProvider()->localCopy($this->name, $isTemporary);
    }

    /**
     * Make sure a thumbnail exists for this record, generating it if needed.
     *
     * Imagick only works on real files, so remote-backed records are pulled to
     * a temp file, thumbnailed locally, then the result is pushed back through
     * the provider. Temp files are always cleaned up.
     *
     * @return bool True when a thumbnail exists afterwards.
     */
    public function ensureThumbnail()
    {
        // Only raster images can be thumbnailed. Archives and documents used to
        // reach Imagick, fail after a full download, and — because no thumbnail
        // was recorded — be retried on every listing. SVG is left alone too: it
        // scales on its own and is already small.
        if (!$this->isThumbnailable()) {
            return false;
        }

        $provider = $this->getProvider();
        $thumbName = $this->thumbnail ?: 'thumb_' . $this->name;

        if ($provider->exists($thumbName)) {
            if ($this->thumbnail !== $thumbName) {
                $this->thumbnail = $thumbName;
                $this->save(false, ['thumbnail']);
            }

            return true;
        }

        if (!extension_loaded('imagick')) {
            return false;
        }

        $sourceIsTemp = false;
        $sourcePath = $provider->localCopy($this->name, $sourceIsTemp);

        if ($sourcePath === false) {
            return false;
        }

        $thumbPath = tempnam(sys_get_temp_dir(), 'thumb_');
        $generated = false;

        try {
            $generated = (bool) $this->generateThumbnail($sourcePath, $thumbPath);

            if ($generated && $provider->write($thumbName, $thumbPath)) {
                $this->thumbnail = $thumbName;
                $this->save(false, ['thumbnail']);
            } else {
                $generated = false;
            }
        } catch (\Throwable $e) {
            Yii::error('Thumbnail generation failed for storage ' . $this->id_storage . ': ' . $e->getMessage(), __METHOD__);
            $generated = false;
        } finally {
            if ($sourceIsTemp && is_file($sourcePath)) {
                @unlink($sourcePath);
            }
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        return $generated;
    }

    /**
     * Whether a thumbnail can be produced for this record.
     *
     * Raster images only: vector and non-image types either gain nothing from
     * a thumbnail or cannot be read by Imagick at all.
     *
     * @return bool
     */
    public function isThumbnailable()
    {
        $mimeType = $this->mime_type;

        if (is_numeric($mimeType)) {
            $mimeType = array_search($mimeType, self::MIME_TYPE);
        }

        return in_array($mimeType, self::THUMBNAIL_ALLOWED_MIMES, true);
    }

    /**
     * Converts file size to a readable format (KB, MB, etc.)
     */
    public function getReadableFileSize()
    {
        $bytes = $this->getProvider()->size($this->name);
        if ($bytes <= 0) {
            return \portalium\storage\Module::t('Unknown size');
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Calculates the total size of the directory in bytes (including subdirectories)
     */
    public function getDirectorySize()
    {
        if ($this->type !== self::TYPE_DIRECTORY) {
            return 0;
        }

        $size = 0;
        $children = self::find()->where(['id_directory' => $this->id_storage])->all();
        
        foreach ($children as $child) {
            if ($child->type === self::TYPE_FILE) {
                $size += $child->getProvider()->size($child->name);
            } else {
                $size += $child->getDirectorySize();
            }
        }
        return $size;
    }

    /**
     * Converts directory size to a readable format
     */
    public function getReadableDirectorySize()
    {
        $bytes = $this->getDirectorySize();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Returns the appropriate FontAwesome icon class based on the mime type
     */
    public function getFileIconClass()
    {
        $mimeType = $this->mime_type;
        if (is_numeric($mimeType)) {
            $mimeType = array_search($mimeType, self::MIME_TYPE);
        }

        if (strpos($mimeType, 'application/pdf') === 0) {
            return 'fa fa-file-pdf-o';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'fa fa-file-video-o';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'fa fa-file-audio-o';
        } elseif (strpos($mimeType, 'text/') === 0) {
            return 'fa fa-file-text-o';
        } elseif (strpos($mimeType, 'application/msword') === 0 || strpos($mimeType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') === 0) {
            return 'fa fa-file-word-o';
        } elseif (strpos($mimeType, 'application/vnd.ms-excel') === 0 || strpos($mimeType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') === 0) {
            return 'fa fa-file-excel-o';
        } elseif (strpos($mimeType, 'application/vnd.ms-powerpoint') === 0 || strpos($mimeType, 'application/vnd.openxmlformats-officedocument.presentationml.presentation') === 0) {
            return 'fa fa-file-powerpoint-o';
        } elseif (strpos($mimeType, 'application/zip') === 0 || strpos($mimeType, 'application/x-rar-compressed') === 0 || strpos($mimeType, 'application/x-tar') === 0 || strpos($mimeType, 'application/gzip') === 0) {
            return 'fa fa-file-archive-o';
        }

        return 'fa fa-file-o';
    }

    /**
     * Checks if the file is an image
     */
    public function isImage()
    {
        $mimeType = $this->mime_type;
        if (is_numeric($mimeType)) {
            $mimeType = array_search($mimeType, self::MIME_TYPE);
        }
        return strpos($mimeType, 'image/') === 0;
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'id_user',
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'id_user',
                ],
                'value' => function ($event) {
                    if ($event->name === \yii\db\ActiveRecord::EVENT_BEFORE_INSERT) {
                        return Yii::$app->user->id;
                    }
                    if ($event->name === \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE) {
                        return $this->getOldAttribute('id_user');
                    }
                },
            ],
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'id_workspace',
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'id_workspace',
                ],
                'value' => function ($event) {
                    if ($event->name === \yii\db\ActiveRecord::EVENT_BEFORE_INSERT) {
                        return Yii::$app->workspace->id ?? null;
                    }
                    if ($event->name === \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE) {
                        return $this->getOldAttribute('id_workspace');
                    }
                },
            ],
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'id_directory',
                ],
                'value' => function ($event) {
                    return $this->getOldAttribute('id_directory');
                },
            ],
        ];
    }

    public function init()
    {
        $this->on(self::EVENT_BEFORE_DELETE, function ($event) {
            \Yii::$app->trigger(Module::EVENT_BEFORE_DELETE, new Event(['payload' => $event->data]));
            Event::trigger(Yii::$app->getModules(), Module::EVENT_BEFORE_DELETE, new Event(['payload' => $event->data]));
        }, $this);
    }

    public static function tableName()
    {
        return '{{%' . Module::$tablePrefix . 'storage}}';
    }

    public function rules()
    {
        return [
            [['type'], 'safe'],
            [['type'], 'in', 'range' => [self::TYPE_FILE, self::TYPE_DIRECTORY]],
            [['type'], 'default', 'value' => self::TYPE_FILE],
            [['title'], 'required', 'when' => function ($model) {
                return $model->type === self::TYPE_FILE;
            }, 'whenClient' => "function (attribute, value) {
            return $('#upload-type').val() === 'file';
        }"],
            [['name'], 'required', 'when' => function ($model) {
                return $model->type === self::TYPE_DIRECTORY;
            }],
            [['name', 'title', 'thumbnail'], 'string', 'max' => 255],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_user' => 'id_user']],
            [['id_directory'], 'integer'],
            [['id_directory'], 'validateNotSelfParent'],
            [['file', 'access', 'hash_file', 'id_workspace', 'allowedExtensions', 'access_count', 'date_last_access'], 'safe'],
            [['disk'], 'string', 'max' => 32],
            [['id_provider'], 'integer'],
            ['mime_type', 'integer'],
            ['access', 'default', 'value' => self::ACCESS_PRIVATE],
            ['access_count', 'integer', 'min' => 0],
            ['date_last_access', 'datetime', 'format' => 'php:Y-m-d H:i:s']
        ];
    }

    /**
     * Validates that a directory cannot be its own parent (directly or indirectly).
     */
    public function validateNotSelfParent($attribute, $params)
    {
        if ($this->type !== self::TYPE_DIRECTORY || empty($this->id_directory)) {
            return;
        }

        // Cannot be its own parent
        if ($this->id_storage && $this->id_directory == $this->id_storage) {
            $this->addError($attribute, Module::t('A folder cannot be its own parent.'));
            return;
        }

        // Check for circular reference: walk up the tree
        $visited = [$this->id_storage];
        $parentId = $this->id_directory;
        $maxDepth = 100;

        while ($parentId !== null && $maxDepth-- > 0) {
            if (in_array($parentId, $visited)) {
                $this->addError($attribute, Module::t('Circular folder reference detected.'));
                return;
            }
            $visited[] = $parentId;
            $parent = self::findOne(['id_storage' => $parentId, 'type' => self::TYPE_DIRECTORY]);
            $parentId = $parent ? $parent->id_directory : null;
        }
    }

    public function attributeLabels()
    {
        return [
            'id_storage' => Module::t('Id Storage'),
            'type' => Module::t('Type'),
            'name' => Module::t('Name'),
            'thumbnail' => Module::t('Thumbnail'),
            'title' => Module::t('Title'),
            'id_user' => Module::t('Id User'),
            'mime_type' => Module::t('Mime Type'),
            'id_workspace' => Module::t('Workspace'),
            'access' => Module::t('Access'),
            'hash_file' => Module::t('Hash File'),
            'id_directory' => Module::t('Directory'),
            'id_provider' => Module::t('Provider'),
            'provider_file_id' => Module::t('Provider File Id'),
            'provider_meta' => Module::t('Provider Meta'),
        ];
    }

    // ─── Directory-related relationships (self-referencing) ─────

    /**
     * Parent directory (Storage record with type = 'directory').
     * @return \yii\db\ActiveQuery
     */
    public function getParentDirectory()
    {
        return $this->hasOne(self::class, ['id_storage' => 'id_directory']);
    }

    /**
     * All direct children (files + subdirectories) inside this directory.
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(self::class, ['id_directory' => 'id_storage']);
    }

    /**
     * Direct child directories only.
     * @return \yii\db\ActiveQuery
     */
    public function getChildDirectories()
    {
        return $this->getChildren()->andWhere(['type' => self::TYPE_DIRECTORY]);
    }

    /**
     * Direct child files only.
     * @return \yii\db\ActiveQuery
     */
    public function getChildFiles()
    {
        return $this->getChildren()->andWhere(['type' => self::TYPE_FILE]);
    }

    /**
     * Helper: is this a directory?
     * @return bool
     */
    public function isDirectory()
    {
        return $this->type === self::TYPE_DIRECTORY;
    }

    /**
     * Helper: is this a file?
     * @return bool
     */
    public function isFile()
    {
        return $this->type === self::TYPE_FILE;
    }

    /**
     * Alias for id_directory (backward compat with old StorageDirectory.id_parent)
     * @return int|null
     */
    public function getIdParent()
    {
        return $this->id_directory;
    }

    /**
     * Get all child items count (files + subdirectories) recursively
     * @return int
     */
    public function getChildItemsCount()
    {
        $count = 0;

        // Count direct children files
        $count += self::find()->where(['id_directory' => $this->id_storage, 'type' => self::TYPE_FILE])->count();

        // Count subdirectories and their contents recursively
        $subdirectories = self::find()->where(['id_directory' => $this->id_storage, 'type' => self::TYPE_DIRECTORY])->all();
        foreach ($subdirectories as $subdir) {
            $count++; // Count the subdirectory itself
            $count += $subdir->getChildItemsCount();
        }

        return $count;
    }

    /**
     * Check if directory is shared with a specific user
     * Also checks parent directories (hierarchical)
     * @param int $id_user User ID
     * @param string $requiredPermission Required permission level
     * @return bool
     */
    public function isDirectorySharedWith($id_user, $requiredPermission = StorageShare::PERMISSION_VIEW)
    {
        if ($this->type !== self::TYPE_DIRECTORY) {
            return false;
        }
        return StorageShare::hasAccess($id_user, null, $this, $requiredPermission);
    }

    /**
     * Check if current user can access this directory
     * Considers: ownership, shares (including parent), workspace membership
     * @param string $requiredPermission Required permission level
     * @return bool
     */
    public function canAccessDirectory($requiredPermission = StorageShare::PERMISSION_VIEW)
    {
        if ($this->type !== self::TYPE_DIRECTORY) {
            return false;
        }

        $userId = Yii::$app->user->id;

        // Owner can always access
        if ($this->id_user == $userId) {
            return true;
        }

        // Check if shared with user (includes parent directories)
        if ($this->isDirectorySharedWith($userId, $requiredPermission)) {
            return true;
        }

        // Check workspace access
        if (
            isset($this->id_workspace) && $this->id_workspace &&
            Yii::$app->workspace->can('storage', 'storageWebDefaultIndex', ['model' => $this])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Upload a folder (multiple files) creating directory entries in storage.
     *
     * Large folders are sent in several requests: the first one creates the
     * top-level directory, the following ones pass its id as $rootId so their
     * files land in the same tree instead of a new copy of it.
     *
     * @param \yii\web\UploadedFile[] $uploadedFiles
     * @param int|null $initialParentId Parent storage id (directory type)
     * @param int|null $rootId Existing top-level directory of this upload
     * @return bool
     */
    public function uploadFolder($uploadedFiles, $initialParentId = null, $rootId = null)
    {
        if (empty($uploadedFiles)) {
            $this->addError('file', Module::t('No files were uploaded'));
            return false;
        }

        $userId = Yii::$app->user->id;
        $workspaceId = $this->id_workspace ?? (Yii::$app->workspace->id ?? 1);

        $directories = []; // relative path → storage id
        $success = true;

        foreach ($uploadedFiles as $file) {
            $fullPath = $file->fullPath ?? $file->name;
            $pathParts = explode('/', $fullPath);
            $fileName = array_pop($pathParts);

            $parentStorageId = $this->resolveFolderPath($pathParts, $initialParentId, $rootId, $workspaceId, $directories);
            if ($parentStorageId === false) {
                return false;
            }

            if (!empty($fileName)) {
                $storage = new self();
                $storage->type = self::TYPE_FILE;
                $storage->title = pathinfo($fileName, PATHINFO_FILENAME);
                $storage->name = $fileName;
                $storage->id_directory = $parentStorageId;
                $storage->id_user = $userId;
                $storage->id_workspace = $workspaceId;
                $storage->file = $file;

                $mimeType = $storage->getMIMEType($fileName);
                $storage->mime_type = self::MIME_TYPE[$mimeType] ?? count(self::MIME_TYPE);
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
     * Creates the directory tree of a folder upload before its files are sent.
     *
     * Batches of the same upload run in parallel; creating every directory up
     * front keeps two batches from creating the same subdirectory twice.
     *
     * @param string[] $paths Relative directory paths, e.g. "photos/2024"
     * @param int|null $initialParentId Parent storage id (directory type)
     * @return bool
     */
    public function createFolderTree(array $paths, $initialParentId = null)
    {
        $workspaceId = $this->id_workspace ?? (Yii::$app->workspace->id ?? 1);
        $directories = [];

        foreach ($paths as $path) {
            $parts = array_values(array_filter(explode('/', (string) $path), 'strlen'));
            if (empty($parts)) {
                continue;
            }
            if ($this->resolveFolderPath($parts, $initialParentId, $this->rootDirectoryId, $workspaceId, $directories) === false) {
                return false;
            }
        }

        if ($this->rootDirectoryId === null) {
            $this->addError('name', Module::t('No files were uploaded'));
            return false;
        }

        return true;
    }

    /**
     * Returns the storage id of the deepest directory in $pathParts, creating
     * missing ones. With $rootId the top level is that directory and existing
     * subdirectories under it are reused.
     *
     * @param string[] $pathParts
     * @param int|null $initialParentId
     * @param int|null $rootId
     * @param int|null $workspaceId
     * @param array $directories Cache of relative path → storage id
     * @return int|null|false Directory id, the initial parent when there is no directory part, false on error
     */
    private function resolveFolderPath(array $pathParts, $initialParentId, $rootId, $workspaceId, array &$directories)
    {
        $currentPath = '';
        $parentId = $initialParentId;

        foreach ($pathParts as $depth => $folderName) {
            $currentPath = $depth === 0 ? $folderName : $currentPath . '/' . $folderName;

            if (!isset($directories[$currentPath])) {
                $existing = false;
                if ($rootId !== null && $depth === 0) {
                    $existing = (int) $rootId;
                } elseif ($rootId !== null) {
                    $existing = self::find()
                        ->select('id_storage')
                        ->where(['type' => self::TYPE_DIRECTORY, 'name' => $folderName, 'id_directory' => $parentId])
                        ->orderBy(['id_storage' => SORT_ASC])
                        ->scalar();
                }

                if ($existing) {
                    $directories[$currentPath] = (int) $existing;
                } else {
                    $dir = new self();
                    $dir->type = self::TYPE_DIRECTORY;
                    $dir->name = $folderName;
                    $dir->title = $folderName;
                    $dir->mime_type = 0;
                    $dir->id_workspace = $workspaceId;
                    $dir->date_create = date('Y-m-d H:i:s');
                    $dir->date_update = date('Y-m-d H:i:s');
                    $dir->id_directory = $parentId;

                    if (!$dir->save()) {
                        foreach ($dir->errors as $attribute => $errors) {
                            foreach ($errors as $error) {
                                $this->addError($attribute, $error);
                            }
                        }
                        return false;
                    }
                    $directories[$currentPath] = $dir->id_storage;
                }

                if ($depth === 0 && $this->rootDirectoryId === null) {
                    $this->rootDirectoryId = $directories[$currentPath];
                }
            }
            $parentId = $directories[$currentPath];
        }

        return $parentId;
    }

    /**
     * (@inheritdoc)
     */
    public function upload()
    {
        if (empty($this->type)) {
            $this->type = self::TYPE_FILE;
        }

        if (!$this->validate()) {
            return false;
        }

        if (!$this->file) {
            return $this->save();
        }

        $path = Yii::getAlias('@app/../data');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if (!is_writable($path)) {
            Yii::error('Upload path is not writable, you might have forgotten to set the correct permissions, path: ' . $path, __METHOD__);
            return false;
        }

        $filename = md5(rand()) . '.' . $this->file->extension;

        if (!empty($this->allowedExtensions) && is_array($this->allowedExtensions)) {
            $fileExtension = strtolower($this->file->extension);
            $normalizedAllowed = array_map('strtolower', $this->allowedExtensions);
            
            if (!in_array($fileExtension, $normalizedAllowed)) {
                $this->addError('file', 'Only files with the following extensions are allowed: ' . implode(', ', $this->allowedExtensions));
                return false;
            }
        }

        $this->date_create = date('Y-m-d H:i:s');
        $this->date_update = date('Y-m-d H:i:s');

        $provider = StorageProviderFactory::getDefault();

        // Write the bytes first: a failed upload must not leave a database row
        // pointing at a file that does not exist.
        if (!$provider->write($filename, $this->file->tempName)) {
            $this->addError('file', Module::t('The file could not be stored.'));

            return false;
        }

        $mimeType = $this->getMIMEType($filename);
        $this->disk = $provider->getName();
        $this->id_provider = $provider->getProviderId();
        $this->name = $filename;
        $this->mime_type = self::MIME_TYPE[$mimeType] ?? self::MIME_TYPE['other'];
        $this->id_workspace = Yii::$app->workspace->id;
        if (!$this->save()) {
            $provider->delete($filename);

            return false;
        }

        return true;
    }


    /**
     * Get MIME type for a file
     * Returns one of the predefined MIME types or 'other' for all other types
     * @param string|null $filename The file name
     * @return string The MIME type
     */
    public function getMIMEType($filename)
    {
        // Check if filename is empty or null
        if (empty($filename)) {
            return 'other';
        }

        $ext = strtolower(substr(strrchr($filename, '.'), 1));
        switch ($ext) {
            case 'zip':
                return 'application/zip';
            case 'rar':
                return 'application/x-rar-compressed';
            case '7z':
                return 'application/x-7z-compressed';
            case 'tar':
                return 'application/x-tar';
            case 'gz':
                return 'application/gzip';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'pdf':
                return 'application/pdf';
            case 'doc':
                return 'application/msword';
            case 'docx':
                return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            case 'xls':
                return 'application/vnd.ms-excel';
            case 'xlsx':
                return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            case 'ppt':
                return 'application/vnd.ms-powerpoint';
            case 'pptx':
                return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            case 'mp4':
                return 'video/mp4';
            case 'mp3':
                return 'audio/mpeg';
            case 'avi':
                return 'video/x-msvideo';
            case 'mov':
                return 'video/quicktime';
            case 'mkv':
                return 'video/x-matroska';
            case 'zip':
                return 'application/zip';
            case 'rar':
                return 'application/x-rar-compressed';
            case '7z':
                return 'application/x-7z-compressed';
            case 'tar':
                return 'application/x-tar';
            case 'gz':
                return 'application/gzip';
            case 'txt':
                return 'text/plain';
            case 'csv':
                return 'text/csv';
            case 'html':
            case 'htm':
                return 'text/html';
            case 'xml':
                return 'application/xml';
            case 'json':
                return 'application/json';
            case 'caffemodel':
                return 'application/vnd.caffemodel+json';
            case 'pb':
                return 'application/pb';
            case 'onnx':
                return 'application/onnx';
            case 'weights':
                return 'application/weights';
            case 'cfg':
                return 'application/cfg';
            case 'pbtxt':
                return 'application/pbtxt';
            case 'prototxt':
                return 'application/prototxt';
            case 'pth':
                return 'application/pth';
            default:
                if (function_exists('finfo_open') && file_exists($filename)) {
                    $finfo = finfo_open(FILEINFO_MIME);
                    $detectedMime = finfo_file($finfo, $filename);
                    finfo_close($finfo);
                    if ($detectedMime) {
                        $detectedMime = explode(';', $detectedMime)[0];
                        if (array_key_exists($detectedMime, self::MIME_TYPE)) {
                            return $detectedMime;
                        }
                    }
                }
                return 'other';
        }
    }

    public static function getMimeTypeList()
    {
        return array_flip(self::MIME_TYPE);
    }

    public static function getAccesses()
    {
        return [
            self::ACCESS_PUBLIC => Module::t('Public'),
            self::ACCESS_PRIVATE => Module::t('Private'),
        ];
    }

     /**
     * Get the URL for accessing the file
     * @return string
     */
    public function getFilePath()
    {
        return Yii::$app->urlManager->baseUrl . '/data/' . $this->name;
    }

    public function getFileUrl()
    {
        return Yii::$app->urlManager->baseUrl . '/storage/default/get-file?id=' . $this->id_storage;
    }

    /**
     * Get the file storage directory path (physical file system path)
     * @return string
     */
    /**
     * Local storage directory.
     *
     * @deprecated Only valid for local-disk records; use getProvider() instead.
     */
    public static function getStorageDir()
    {
        return (new \portalium\storage\providers\LocalProvider())->getBasePath();
    }

    /**
     * Get full file path for a storage item (physical file system path)
     * @param string|null $filename If null, uses $this->name
     * @return string
     */
    public function getFullFilePath($filename = null)
    {
        if ($filename === null) {
            $filename = $this->name;
        }
        return self::getStorageDir() . '/' . $filename;
    }

    /**
     * Get full thumbnail file path (physical file system path)
     * @return string|null
     */
    public function getThumbnailFullPath()
    {
        if (empty($this->thumbnail)) {
            return null;
        }
        return self::getStorageDir() . '/' . $this->thumbnail;
    }

    /**
     * Get default thumbnail file path based on current file name (physical file system path)
     * @return string
     */
    public function getDefaultThumbnailFullPath()
    {
        return self::getStorageDir() . '/' . 'thumb_' . $this->name;
    }

    public function deleteFile()
    {
        $provider = $this->getProvider();

        $provider->delete($this->name);

        if ($this->thumbnail) {
            $provider->delete($this->thumbnail);
        }

        return $this->delete();
    }


    public function copyFile()
    {
        $provider = $this->getProvider();

        $newModel = new Storage();
        $newModel->attributes = $this->attributes;
        $newModel->id_storage = null;
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);
        $newFileName = md5(rand()) . "." . $extension;
        $newModel->title = $this->generateNewTitle($this->title);

        if ($provider->copy($this->name, $newFileName)) {
            $newModel->disk = $provider->getName();
            $newModel->id_provider = $provider->getProviderId();
            $newModel->name = $newFileName;

            if ($this->thumbnail) {
                $thumbExtension = pathinfo($this->thumbnail, PATHINFO_EXTENSION);
                $newThumbName = 'thumb_' . pathinfo($newFileName, PATHINFO_FILENAME) . '.' . $thumbExtension;

                if ($provider->copy($this->thumbnail, $newThumbName)) {
                    $newModel->thumbnail = $newThumbName;
                }
            }

            if ($newModel->save()) {
                return $newModel;
            } else {
                // Roll back the copied bytes so a failed save leaves nothing behind.
                $provider->delete($newFileName);

                if (!empty($newModel->thumbnail)) {
                    $provider->delete($newModel->thumbnail);
                }

                return false;
            }
        }
        return false;
    }

    private function generateNewTitle($originalTitle)
    {
        $info = pathinfo($originalTitle);
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';

        $filename = $info['filename'];
        if (preg_match('/^(.*)\((\d+)\)$/', $filename, $matches)) {
            $filename = $matches[1];
        }

        $counter = 1;
        $newTitle = "{$filename} ({$counter}){$extension}";

        while (self::find()->where(['title' => $newTitle])->exists()) {
            $counter++;
            $newTitle = "{$filename} ({$counter}){$extension}";
        }

        return $newTitle;
    }

    public function cloneStorage()
    {
        $newStorage = new Storage();
        $newStorage->type = self::TYPE_FILE;
        $newStorage->title = $this->title;
        $newStorage->id_user = Yii::$app->user->id;
        $newStorage->mime_type = $this->mime_type;
        $newStorage->id_workspace = Yii::$app->workspace->id;
        $newStorage->access = self::ACCESS_PUBLIC;

        $extension = pathinfo($this->name, PATHINFO_EXTENSION);
        $filename = md5(rand()) . "." . $extension;

        // The copy stays on the provider that already holds the source bytes.
        $provider = $this->getProvider();

        try {
            if ($provider->copy($this->name, $filename)) {
                $newStorage->disk = $provider->getName();
                $newStorage->id_provider = $provider->getProviderId();
                $newStorage->name = $filename;
                if ($newStorage->save()) {
                    return $newStorage;
                }

                $provider->delete($filename);
            }
        } catch (\Throwable $th) {
            return false;
        }
        return false;
    }

    public function getIconUrl()
    {
        $mimeType = $this->mime_type;
        if (is_numeric($mimeType)) {
            $mimeType = array_search($mimeType, self::MIME_TYPE);
        }

        $iconPath = Yii::$app->view->getAssetManager()->getBundle(\portalium\storage\bundles\IconAsset::class)->baseUrl;

        // The icon follows the mime type alone. Probing the provider for every
        // row used to decide this, which on a remote bucket turned one file
        // listing into dozens of HTTP requests; the fallback branch returned
        // the same unknown-file icon the switch already ends with.
        switch ($mimeType) {
            case 'application/pdf':
                return [
                    'url' => $iconPath . '/pdf-icon.png',
                    'class' => 'non-image'
                ];
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return [
                    'url' => $iconPath . '/doc-icon.png',
                    'class' => 'non-image'
                ];
            case 'application/vnd.ms-excel':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return [
                    'url' => $iconPath . '/xls-icon.png',
                    'class' => 'non-image'
                ];
            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                return [
                    'url' => $iconPath . '/ppt-icon.png',
                    'class' => 'non-image'
                ];
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/png':
            case 'image/svg+xml':
                // Serving this asks for the thumbnail but never builds one; a
                // record without one falls back to its original until
                // `storage/thumbnail/generate` has run.
                $url = Yii::$app->urlManager->baseUrl . '/storage/default/get-file?id=' . $this->id_storage . '&type=thumb';
                return [
                    'url' => $url,
                    'class' => 'image-file'
                ];
            case 'other':
            default:
            return [
                'url' => $iconPath . '/unknown-icon.png',
                'class' => 'non-image'
            ];
        }
    }

    public function getIconClass()
    {
        $mimeType = $this->mime_type;
        if (is_numeric($mimeType)) {
            $mimeType = array_search($mimeType, self::MIME_TYPE);
        }

        switch ($mimeType) {
            case 'application/pdf':
                return 'fa fa-file-pdf-o file-icon pdf';
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return 'fa fa-file-word-o file-icon word';
            case 'application/vnd.ms-excel':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return 'fa fa-file-excel-o file-icon excel';
            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                return 'fa fa-file-powerpoint-o file-icon powerpoint';
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/png':
            case 'image/svg+xml':
                return 'fa fa-file-image-o file-icon image';
            case 'video/mp4':
            case 'video/x-matroska':
            case 'video/x-msvideo':
            case 'video/mov':
                return 'fa fa-file-video-o file-icon video';
            case 'audio/mpeg':
                return 'fa fa-file-audio-o file-icon audio';
            case 'application/x-rar-compressed':
            case 'application/zip':
            case 'application/gzip':
            case 'application/x-tar':
                return 'fa fa-file-archive-o file-icon archive';
            case 'other':
            default:
                return 'fa fa-file file-icon';
        }
    }

    public function fileExists()
    {
        return $this->getProvider()->exists($this->name);
    }

    public function getExtension()
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public static function findForApi()
    {
        $query = parent::find();

        if (Yii::$app->user->can('storageStorageFindAll', ['id_module' => 'storage'])) {
            return $query;
        }

        if (!Yii::$app->user->can('storageStorageFindOwner', ['id_module' => 'storage'])) {
            return $query->andWhere([Module::$tablePrefix . 'storage.access' => self::ACCESS_PUBLIC]);
        }
        $workspaces = WorkspaceUser::find()->select('id_workspace')->where(['id_user' => Yii::$app->user->id])->asArray()->all();
        $workspaces = ArrayHelper::getColumn($workspaces, 'id_workspace');

        if ($workspaces) {
            return $query->andWhere([Module::$tablePrefix . 'storage.id_workspace' => $workspaces])->orWhere([Module::$tablePrefix . 'storage.access' => self::ACCESS_PUBLIC]);
        } else {
            return $query->andWhere([Module::$tablePrefix . 'storage.access' => self::ACCESS_PUBLIC]);
        }
    }

    /**
     * Delete local files that no Storage record points at.
     *
     * Deliberately limited to the local disk: a remote provider's bucket may be
     * shared, so scanning and deleting unknown keys there is not safe.
     */
    public static function cleanOrphanFiles()
    {
        $dataPath = (new \portalium\storage\providers\LocalProvider())->getBasePath();
        if (!$dataPath || !is_dir($dataPath)) {
            return false;
        }

        $files = scandir($dataPath);
        $deletedFiles = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $exists = self::find()->where(['name' => $file])->exists();
            if (!$exists) {
                $filePath = $dataPath . '/' . $file;
                if (is_file($filePath) && unlink($filePath)) {
                    $deletedFiles[] = $file;
                }
            }
        }
        return $deletedFiles;
    }

    /**
     * Delete records whose bytes are missing from their own provider.
     *
     * Each record is checked against the provider named in its `disk` column,
     * so switching the default provider never makes existing rows look orphaned.
     */
    public static function cleanOrphanRecords()
    {
        $deletedRecords = [];
        $storages = self::find()->where(['type' => self::TYPE_FILE])->all();
        foreach ($storages as $storage) {
            if (!$storage->getProvider()->exists($storage->name)) {
                if ($storage->delete()) {
                    $deletedRecords[] = $storage->id_storage;
                }
            }
        }
        return $deletedRecords;
    }

    public static function getAccessList()
    {
        return [
            self::ACCESS_PRIVATE => Module::t('Private'),
            self::ACCESS_PUBLIC => Module::t('Public'),
        ];
    }

    public function getAccessText()
    {
        $list = self::getAccessList();
        return isset($list[$this->access]) ? $list[$this->access] : Module::t('Unknown');
    }


    public function generateThumbnail($sourcePath, $thumbPath, $height = self::THUMBNAIL_DEFAULT_HEIGHT, $targetSizeKB = self::THUMBNAIL_MAX_SIZE_KB)
    {
        if (!extension_loaded('imagick')) {
            throw new \Exception("Imagick extension is not installed.");
        }
        
        if (!file_exists($sourcePath)) {
            throw new \Exception("Source file does not exist: " . $sourcePath);
        }

        // Check write permissions on directory
        $thumbDir = dirname($thumbPath);
        if (!is_dir($thumbDir)) {
            throw new \Exception("Thumbnail directory does not exist: " . $thumbDir);
        }

        if (!is_writable($thumbDir)) {
            throw new \Exception("Thumbnail directory is not writable: " . $thumbDir);
        }

        $originalSizeKB = filesize($sourcePath) / 1024;

        if ($originalSizeKB <= $targetSizeKB) {
            return copy($sourcePath, $thumbPath);
        }

        try {
            $image = new \Imagick($sourcePath);

            $origWidth = $image->getImageWidth();
            $origHeight = $image->getImageHeight();

            if ($origHeight <= $height) {
                return copy($sourcePath, $thumbPath);
            }

            $ratio = $origWidth / $origHeight;
            $width = intval($height * $ratio);
            $image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);

            $quality = 85;
            $minQuality = 30;
            $loopCount = 0;

            do {
                $loopCount++;
                $image->setImageCompressionQuality($quality);

                // PNG is lossless, so lowering the quality does almost nothing
                // to its size: the loop below would spin down to the minimum and
                // still write a full-size file. Thumbnails are previews, so a
                // PNG without transparency is better served as JPEG; one with
                // transparency keeps PNG but gets its palette reduced instead.
                $format = strtolower($image->getImageFormat());

                if ($format === 'png') {
                    if ($image->getImageAlphaChannel()) {
                        $image->setImageFormat('png');
                        $image->quantizeImage(64, \Imagick::COLORSPACE_RGB, 0, false, false);
                    } else {
                        $image->setImageFormat('jpeg');
                    }
                } elseif ($format === 'webp') {
                    $image->setImageFormat('webp');
                } else {
                    $image->setImageFormat('jpeg');
                }

                $imgData = $image->getImagesBlob();
                $fileSizeKB = strlen($imgData) / 1024;

                if ($fileSizeKB <= $targetSizeKB || $quality <= $minQuality) {
                    $writeResult = file_put_contents($thumbPath, $imgData);
                    
                    if ($writeResult === false) {
                        throw new \Exception("Failed to write thumbnail file: " . $thumbPath);
                    }
                    
                    $image->clear();
                    $image->destroy();
                    return true;
                }

                $quality -= 5;
            } while ($quality >= $minQuality);

            $image->clear();
            $image->destroy();
            return false;
        } catch (\ImagickException $e) {
            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public static function generateMissingThumbnails()
    {
        $storages = self::find()->where(['thumbnail' => null])->all();
        $updated = 0;

        foreach ($storages as $storage) {
            if ($storage->type !== self::TYPE_FILE) {
                continue;
            }

            // The stored mime type already says what this is; sniffing the
            // name again told us nothing extra and skipped records whose
            // extension disagreed with what was uploaded.
            if (!$storage->isThumbnailable()) {
                continue;
            }

            if ($storage->ensureThumbnail()) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Gets query for [[Shares]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShares()
    {
        return $this->hasMany(StorageShare::class, ['id_storage' => 'id_storage']);
    }

    /**
     * Gets active shares for this storage
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActiveShares()
    {
        return $this->getShares()
            ->where(['is_active' => 1])
            ->andWhere(['OR',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')]
            ]);
    }

    /**
     * Check if storage is shared with a specific user
     *
     * @param int $id_user User ID
     * @param string $requiredPermission Required permission level
     * @return bool
     */
    public function isSharedWith($id_user, $requiredPermission = StorageShare::PERMISSION_VIEW)
    {
        return StorageShare::hasAccess($id_user, $this, null, $requiredPermission);
    }

    /**
     * Check if current user can access this storage item
     * Considers: ownership, shares, workspace membership
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

        // Check if shared with user
        if ($this->isSharedWith($userId, $requiredPermission)) {
            return true;
        }

        // Check workspace access
        if ($this->id_workspace && Yii::$app->workspace->can('storage', 'storageWebDefaultIndex', ['model' => $this])) {
            return true;
        }

        return false;
    }
}

