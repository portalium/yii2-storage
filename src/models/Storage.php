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
    ];

    public static $allowExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

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

        if (!in_array($this->file->extension, self::$allowExtensions))
            return false;

        if (!$this->file->saveAs($path . '/' . $filename))
            return false;

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
        if (empty($filename)) {
            return 'application/octet-stream';
        }

        $ext = strtolower(substr(strrchr($filename, '.'), 1));
        switch ($ext) {
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
        $baseName = $originalTitle;
        $newTitle = $baseName . '_1';
        $counter = 1;
        while (self::find()->where(['title' => $newTitle])->exists()) {
            $counter++;
            $newTitle = $baseName . '_' . $counter;
        }
        return $newTitle;
    }

    public function getIconUrl()
    {
        $mimeType = $this->mime_type;
        if (is_numeric($mimeType)) {
            $mimeType = array_search($mimeType, self::MIME_TYPE);
        }
        $path = Yii::$app->basePath . '/../data/' . $this->name;
        if (file_exists($path)) {
            switch ($mimeType) {
                case 'application/pdf':
                    return [
                        'url' => 'https://img.icons8.com/?size=100&id=13417&format=png&color=000000',
                        'class' => 'non-image'
                    ];
                case 'application/msword':
                case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    return [
                        'url' => 'https://img.icons8.com/?size=100&id=13674&format=png&color=000000',
                        'class' => 'non-image'
                    ];
                case 'application/vnd.ms-excel':
                case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                    return [
                        'url' => 'https://img.icons8.com/?size=100&id=13654&format=png&color=000000',
                        'class' => 'non-image'
                    ];
                case 'application/vnd.ms-powerpoint':
                case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                    return [
                        'url' => 'https://img.icons8.com/?size=100&id=81726&format=png&color=000000',
                        'class' => 'non-image'
                    ];
                case 'image/jpeg':
                case 'image/png':
                    return [
                        'url' => Yii::$app->urlManager->baseUrl . '/data/' . $this->name,
                        'class' => 'image-file'
                    ];
                default:
                    return [
                        'url' => 'https://img.icons8.com/?size=100&id=12141&format=png&color=000000',
                        'class' => 'non-image'
                    ];
            }
        }
        else {
            return [
                'url' => 'https://img.icons8.com/?size=100&id=12141&format=png&color=000000',
                'class' => 'non-image'
            ];
        }
    }
}
