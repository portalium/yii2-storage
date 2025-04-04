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

    const ACCESS_PUBLIC = 1;
    const ACCESS_PRIVATE = 0;
    const IS_MODAL_TRUE = 1;
    const IS_MODAL_FALSE = 0;

    const MIME_TYPE = [
        'audio/aac' => '0',
        'audio/mpeg' => '1',
        'audio/ogg' => '2',
        'audio/opus' => '3',
        'audio/wav' => '4',
        'audio/webm' => '5',
        'audio/midi audio/x-midi' => '6',
        'video/avi' => '7',
        'video/mpeg' => '8',
        'video/ogg' => '9',
        'video/mp4' => '10',
        'video/webm' => '11',
        'video/3gpp' => '12',
        'image/bmp' => '13',
        'image/gif' => '14',
        'image/vnd.microsoft.icon' => '15',
        'image/jpg' => '16',
        'image/jpeg' => '17',
        'image/png' => '18',
        'image/svg+xml' => '19',
        'image/tiff' => '20',
        'image/webp' => '21',
        'application/x-abiword' => '22',
        'application/x-freearc' => '23',
        'application/vnd.amazon.ebook' => '24',
        'application/octet-stream' => '25',
        'application/x-bzip' => '26',
        'application/x-bzip2' => '27',
        'application/x-csh' => '28',
        'text/css' => '29',
        'text/csv' => '30',
        'application/msword' => '31',
        'application/vnd.ms-fontobject' => '32',
        'application/epub+zip' => '33',
        'application/gzip' => '34',
        'text/html' => '35',
        'text/calendar' => '36',
        'application/java-archive' => '37',
        'text/javascript' => '38',
        'application/json' => '39',
        'application/ld+json' => '40',
        'text/javascript' => '41',
        'application/vnd.apple.installer+xml' => '42',
        'application/vnd.oasis.opendocument.presentation' => '43',
        'application/vnd.oasis.opendocument.spreadsheet' => '44',
        'application/vnd.oasis.opendocument.text' => '45',
        'application/ogg' => '46',
        'font/otf' => '47',
        'application/pdf' => '48',
        'application/x-httpd-php' => '49',
        'application/vnd.ms-powerpoint' => '50',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '51',
        'application/vnd.rar' => '52',
        'application/rtf' => '53',
        'application/x-sh' => '54',
        'application/x-shockwave-flash' => '55',
        'application/x-tar' => '56',
        'font/ttf' => '57',
        'text/plain' => '58',
        'application/vnd.visio' => '59',
        'font/woff' => '60',
        'font/woff2' => '61',
        'application/xhtml+xml' => '62',
        'application/vnd.ms-excel' => '63',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '64',
        'application/xml' => '65',
        'application/vnd.mozilla.xul+xml' => '66',
        'application/zip' => '67',
        'application/x-7z-compressed' => '68',
        'other' => '69',
    ];

    public static $allowExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
    // behaviors id_user is set to current user
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

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        //use prefix from module
        return '{{%' . Module::$tablePrefix . 'storage}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'id_workspace'], 'required'],
            [['name', 'title'], 'string', 'max' => 255],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_user' => 'id_user']],
            [['file', 'access', 'hash_file'], 'safe'],
            ['mime_type', 'integer'],
            ['access', 'default', 'value' => self::ACCESS_PRIVATE]
        ];
    }

    /* //fields add url
    public function fields()
    {
        return array_merge(parent::fields(), [
            'url' => function ($model) {
                return Yii::getAlias('@data') . '/' . $model->name;
            },
        ]);
    } */

    /**
     * {@inheritdoc}
     */
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
        ];
    }

    public static function getMimeTypeList()
    {
        $array = [];
        foreach (self::MIME_TYPE as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $array[$key][$v] = $k;
                }
            } else {
                $array[$value] = $key;
            }
        }
        return $array;
    }

    public function getAllowedExtensions()
    {
        return implode(', ', self::$allowExtensions);
    }

    public static function getAccesses()
    {
        return [
            self::ACCESS_PUBLIC => Module::t('Public'),
            self::ACCESS_PRIVATE => Module::t('Private'),
        ];
    }

    /**
     * (@inheritdoc)
     */
    public function upload()
    {

        if ($this->validate()) {
            if (!$this->file) {
                $this->save();
                return true;
            }

            $path = realpath(Yii::$app->basePath . '/../data');
            $filename = md5(rand()) . "." . $this->file->extension;
            // check if file extension is allowed
            if (in_array($this->file->extension, self::$allowExtensions)) {
                if ($this->file->saveAs($path . '/' . $filename)) {
                    $this->name = $filename;
                    $this->mime_type = self::MIME_TYPE[$this->getMIMEType($path . '/' . $filename)];
                    if ($this->save()) {
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
        return false;
    }

    public function getMIMEType($filename)
    {
        $mime_types = self::MIME_TYPE;
        $ext = strtolower(substr(strrchr($filename, '.'), 1));
        if (array_key_exists($ext, $mime_types)) {
            if (is_array($mime_types[$ext])) {
                //; charset=binary to ''
                $mime_types[$ext][0] = str_replace('; charset=binary', '', $mime_types[$ext][0]);
                return $mime_types[$ext][0];
            } else {
                $mime_types[$ext] = str_replace('; charset=binary', '', $mime_types[$ext]);
                return $mime_types[$ext];
            }
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            $mimetype = explode(';', $mimetype);
            return $mimetype[0];
        } else {
            return 'application/octet-stream';
        }
    }

    public function deleteFile($filename)
    {
        $path = realpath(Yii::$app->basePath . '/../data');
        if (file_exists($path . '/' . $filename)) {
            if (unlink($path . '/' . $filename)) {
                return true;
            }
        }
        return true;
    }

    public function getFilePath()
    {
        // $path =  Yii::$app->request->baseUrl . '/' . Yii::$app->setting->getValue('storage::path');
        // return $path . '/' . $this->name;
        return '/storage/default/get-file?id=' . $this->id_storage;
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
                // system("cp -r " . $path . '/' . $this->name . " " . $path . '/' . $filename);
                if ($newStorage->save()) {
                    return $newStorage;
                }
            }
        } catch (\Throwable $th) {
            return false;
        }
        return false;
    }

    public function getExtension()
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public static function getWorkspaces()
    {
        $workspaces = Workspace::find()->all();
        $array = [];
        foreach ($workspaces as $workspace) {
            $array[$workspace->id_workspace] = $workspace->name . ' (' . (isset($workspace->user) ? $workspace->user->username : '') . ')';
        }
        return $array;
    }

    public function fileExists()
    {
        $path = realpath(Yii::$app->basePath . '/../data');
        return file_exists($path . '/' . $this->name);
    }

    public function afterDelete()
    {
        $this->deleteFile($this->name);
        return parent::afterDelete();
    }



    public function beforeSave($insert)
    {
        if (Yii::$app->workspace->checkOwner($this->id_workspace)) {
            return parent::beforeSave($insert);
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $this->hash_file = md5_file(Yii::$app->basePath . '/../data/' . $this->name);
            $this->save();
        }
        return parent::afterSave($insert, $changedAttributes);
    }

    public function afterFind()
    {
        if (!$this->hash_file) {
            $file = realpath(Yii::$app->basePath . '/../data') . '/' . $this->name;
            if (file_exists($file)) {
                $this->hash_file = md5_file($file);
                $this->save();
            }
        }
        return parent::afterFind();
    }
    /**
     * Dosya MIME tipine göre ikon URL'si döndürür
     * @return string Dosya ikonu URL'si
     */
    public function getIconUrl()
    {
        $mimeType = $this->mime_type;
        if (is_numeric($mimeType)) {
            $mimeType = array_search($mimeType, self::MIME_TYPE);
        }

        if (!$mimeType) {
            return 'https://img.icons8.com/ios/452/file.png'; // default
        }

        if (strpos($mimeType, 'image/') === 0) {
            return 'https://img.icons8.com/ios/452/image-file.png';
        } else if (strpos($mimeType, 'audio/') === 0) {
            return 'https://img.icons8.com/ios/452/audio-file.png';
        } else if (strpos($mimeType, 'video/') === 0) {
            return 'https://img.icons8.com/ios/452/video-file.png';
        } else if (strpos($mimeType, 'text/') === 0) {
            return 'https://img.icons8.com/ios/452/text-file.png';
        } else {
            switch ($mimeType) {
                case 'application/pdf':
                    return 'https://img.icons8.com/ios/452/pdf.png';
                case 'application/msword':
                case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    return 'https://img.icons8.com/ios/452/doc.png';
                case 'application/vnd.ms-excel':
                case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                    return 'https://img.icons8.com/ios/452/xls.png';
                case 'application/vnd.ms-powerpoint':
                case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                    return 'https://img.icons8.com/ios/452/ppt.png';
                case 'application/zip':
                case 'application/x-rar-compressed':
                case 'application/x-7z-compressed':
                    return 'https://img.icons8.com/ios/452/zip.png';
                default:
                    return 'https://img.icons8.com/ios/452/file.png';
            }
        }
    }
}
