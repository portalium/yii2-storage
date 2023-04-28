<?php

namespace portalium\storage\models;

use portalium\workspace\models\WorkspaceUser;
use Yii;
use portalium\storage\Module;
use yii\helpers\ArrayHelper;
use portalium\user\models\User;

/**
 * This is the model class for table "{{%storage_storage}}".
 *
 * @property int $id_storage
 * @property string $name
 * @property string $title
 * @property string $id_user
 * @property string $mime_type
 */
class Storage extends \yii\db\ActiveRecord
{
    public $file;

    const MIME_TYPE = [
        'audio/aac' => '0',
        'audio/mpeg' => '1',
        'audio/ogg' => '2',
        'audio/opus' => '3',
        'audio/wav' => '4',
        'audio/webm' => '5',
        'audio/midi audio/x-midi' => '6',
        'video/x-msvideo' => '7',
        'video/mpeg' => '8',
        'video/ogg' => '9',
        'video/mp2t' => '10',
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
            ['file', 'safe'],
            ['mime_type', 'integer'],
        ];
    }

    //fields add url
    public function fields()
    {
        return array_merge(parent::fields(), [
            'url' => function ($model) {
                return Yii::getAlias('@data') . '/' . $model->name;
            },
        ]);
    }

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
                    if($this->save()){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else{
                return false;
            }
        }
        return false;
    }

    protected function getMIMEType($filename)
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
            unlink($path . '/' . $filename);
        }
    }

    public static function find()
    {
        $activeWorkspaceId = WorkspaceUser::getActiveWorkspaceId();
        $query = parent::find();
        if (Yii::$app->user->can('storageStorageFindAll', ['id_module' => 'storage'])) {
            return $query;
        }
        if (!Yii::$app->user->can('storageStorageFindOwner', ['id_module' => 'storage'])) {
            return $query->andWhere('0=1');
        }
        if ($activeWorkspaceId) {
            $query->andWhere([Module::$tablePrefix . 'storage.id_workspace' => $activeWorkspaceId]);
        }else{
            return $query->andWhere('0=1');
        }
        return $query;
    }



    public function beforeSave($insert)
    {
        if (Yii::$app->workspace->checkOwner($this->id_workspace)) {
            return parent::beforeSave($insert);
        }
        return false;
    }

}
