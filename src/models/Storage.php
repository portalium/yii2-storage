<?php

namespace portalium\storage\models;

use portalium\workspace\models\WorkspaceUser;
use Yii;
use portalium\storage\Module;
use yii\helpers\ArrayHelper;
use portalium\user\models\User;
use yii\web\UploadedFile;
use portalium\base\Event;
use portalium\workspace\models\Workspace;

/**
 * This is the model class for table "{{%storage_storage}}".
 *
 * @property int $id_storage
 * @property string $name
 * @property string $title
 * @property string $id_user
 * @property string $mime_type
 * @property string $id_workspace
 * @property string $access
 * @property string $hash_file
 */
class Storage extends \yii\db\ActiveRecord
{
    public $file;
    public $type;

    const ACCESS_PUBLIC = 1;
    const ACCESS_PRIVATE = 0;
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
    ];

    public static $allowExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'mp4', 'mp3', 'avi', 'mov', 'mkv', 'zip', 'rar', 'txt', 'pt', 'csv', 'html', 'htm', 'xml', 'json', 'tar', 'gz', '7z', 'svg'];

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
            ]
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
            [['title'], 'required', 'when' => function ($model) {
                return $model->type === 'file';
            }, 'whenClient' => "function (attribute, value) {
            return $('#upload-type').val() === 'file';
        }"],
            [['name', 'title'], 'string', 'max' => 255],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_user' => 'id_user']],
            [['id_directory'], 'integer'],
            [['file', 'access', 'hash_file', 'id_workspace'], 'safe'],
            ['mime_type', 'integer'],
            ['access', 'default', 'value' => self::ACCESS_PRIVATE]
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_storage' => Module::t('Id Storage'),
            'name' => Module::t('Name'),
            'title' => Module::t('Title'),
            'id_user' => Module::t('Id User'),
            'mime_type' => Module::t('Mime Type'),
            'id_workspace' => Module::t('Workspace'),
            'access' => Module::t('Access'),
            'hash_file' => Module::t('Hash File'),
            'id_directory' => Module::t('Directory'),
        ];
    }

    public function upload()
    {
        if (!$this->validate())
            return false;
        if (!$this->file)
            return $this->save();

        $path = realpath(Yii::$app->basePath . '/../data');
        $filename = md5(uniqid(rand(), true)) . '.' . $this->file->extension;
        $hash = md5_file($this->file->tempName);
        if (!in_array($this->file->extension, self::$allowExtensions)) {
            Yii::warning('File extension not allowed: ' . $this->file->extension, __METHOD__);
            return false;
        }

        if (!$this->file->saveAs($path . '/' . $filename)) {
            Yii::warning('File could not be saved: ' . $this->file->tempName, __METHOD__);
            return false;
        }
        $this->name = $filename;
        $this->hash_file = $hash;
        $this->mime_type = self::MIME_TYPE[$this->getMIMEType($path . '/' . $filename)];
        $this->id_workspace = Yii::$app->workspace->id;
        $this->id_user = Yii::$app->user->id;
        return $this->save();
    }


    /**
     * Get MIME type for a file
     * @param string|null $filename The file name
     * @return string The MIME type
     */
    public function getMIMEType($filename)
    {
        // Check if filename is empty or null
        if (empty($filename)) {
            return 'application/octet-stream'; // Default MIME type for unknown files
        }

        $ext = strtolower(substr(strrchr($filename, '.'), 1));
        Yii::warning('MIME type requested for file: ' . $filename . ' with extension: ' . $ext, __METHOD__);
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
                Yii::warning('MIME type for mp4 requested', __METHOD__);
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
            default:
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME);
                    $mimetype = finfo_file($finfo, $filename);
                    finfo_close($finfo);
                    $mimetype = explode(';', $mimetype);
                    return $mimetype[0];
                } else {
                    return 'application/octet-stream';
                }
        }
    }

    public function getFilePath()
    {
        // $path =  Yii::$app->request->baseUrl . '/' . Yii::$app->setting->getValue('storage::path');
        // return $path . '/' . $this->name;
        return '/storage/default/get-file?id=' . $this->id_storage;
    }

    public function deleteFile()
    {
        $filePath = Yii::$app->basePath . '/../data/' . $this->name;
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                return $this->delete();
            }
        }
        return false;
    }

    public function copyFile()
    {
        $path = realpath(Yii::$app->basePath . '/../data');
        $sourcePath = $path . '/' . $this->name;
        $newModel = new Storage();
        $newModel->attributes = $this->attributes;
        $newModel->id_storage = null;
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);
        $newFileName = md5(rand()) . "." . $extension;
        $newFilePath = $path . '/' . $newFileName;
        $newModel->title = $this->generateNewTitle($this->title);

        if (copy($sourcePath, $newFilePath)) {
            $newModel->name = $newFileName;
            if ($newModel->save()) {
                return $newModel;
            } else {
                if (file_exists($newFilePath)) {
                    unlink($newFilePath);
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
        $newTitle = "{$filename}({$counter}){$extension}";

        while (self::find()->where(['title' => $newTitle])->exists()) {
            $counter++;
            $newTitle = "{$filename}({$counter}){$extension}";
        }

        return $newTitle;
    }

    public function cloneStorage()
    {
        $newStorage = new Storage();
        $newStorage->title = $this->title;
        $newStorage->id_user = Yii::$app->user->id;
        $newStorage->mime_type = $this->mime_type;
        $newStorage->id_workspace = Yii::$app->workspace->id;
        $newStorage->access = self::ACCESS_PUBLIC;

        $path = realpath(Yii::$app->basePath . '/../data');
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);
        $filename = md5(rand()) . "." . $extension;
        try {
            if (copy($path . '/' . $this->name, $path . '/' . $filename)) {
                $newStorage->name = $filename;
                if ($newStorage->save()) {
                    return $newStorage;
                }
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
        $path = Yii::$app->basePath . '/../data/' . $this->name;
        $iconPath = Yii::$app->view->getAssetManager()->getBundle(\portalium\storage\bundles\IconAsset::class)->baseUrl;
        if (file_exists($path)) {
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
                    return [
                        'url' => Yii::$app->urlManager->baseUrl . '/data/' . $this->name,
                        'class' => 'image-file'
                    ];
                default:
                    return [
                        'url' => $iconPath . '/unknown-icon.png',
                        'class' => 'non-image'
                    ];
            }
        } else {
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
            default:
                return 'fa fa-file file-icon';
        }
    }

    public function fileExists()
    {
        $path = realpath(Yii::$app->basePath . '/../data');
        return file_exists($path . '/' . $this->name);
    }

    public function getExtension()
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public static function findForApi()
    {
        $query = parent::find();

        //        if (Yii::$app->user->can('storageStorageFindAll', ['id_module' => 'storage'])) {
        return $query;
        //        }

        if (!Yii::$app->user->can('storageStorageFindOwner', ['id_module' => 'storage'])) {
            // get public files
            return $query->andWhere([Module::$tablePrefix . 'storage.access' => self::ACCESS_PUBLIC]);
        }
        $workspaces = WorkspaceUser::find()->select('id_workspace')->where(['id_user' => Yii::$app->user->id])->asArray()->all();
        $workspaces = ArrayHelper::getColumn($workspaces, 'id_workspace');

        if ($workspaces) {
            // $query->andWhere([Module::$tablePrefix . 'storage.id_workspace' => $activeWorkspaceId])->orWhere([Module::$tablePrefix . 'storage.access' => self::ACCESS_PUBLIC]);
            return $query->andWhere([Module::$tablePrefix . 'storage.id_workspace' => $workspaces])->orWhere([Module::$tablePrefix . 'storage.access' => self::ACCESS_PUBLIC]);
        } else {
            return $query->andWhere([Module::$tablePrefix . 'storage.access' => self::ACCESS_PUBLIC]);
        }
    }

    public static function cleanOrphanFiles()
    {
        $dataPath = realpath(Yii::$app->basePath . '/../data');
        if (!$dataPath || !is_dir($dataPath)) {
            return false;
        }

        $files = scandir($dataPath);
        $deletedFiles = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            // Check if file exists in storage
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

    public static function cleanOrphanRecords()
    {
        $dataPath = realpath(Yii::$app->basePath . '/../data');
        if (!$dataPath || !is_dir($dataPath)) {
            return false;
        }

        $deletedRecords = [];
        $storages = self::find()->all();
        foreach ($storages as $storage) {
            $filePath = $dataPath . '/' . $storage->name;
            if (!file_exists($filePath)) {
                if ($storage->delete()) {
                    $deletedRecords[] = $storage->id_storage;
                }
            }
        }
        return $deletedRecords;
    }
}
