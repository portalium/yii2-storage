<?php

namespace portalium\storage\models;

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
 *  */
class Storage extends \yii\db\ActiveRecord
{
    public $file;

        
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
            [['title'], 'required'],
            [['name', 'title'], 'string', 'max' => 255],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_user' => 'id_user']],
            ['file', 'safe'],
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
        ];
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
            $path = realpath(Yii::$app->basePath ."/../". Yii::$app->setting->getValue('app::data'));
            /*echo var_dump(is_dir($path));
            exit;*/
            if(!is_dir($path)){
                \Yii::$app->session->addFlash('error', Module::t('Error, directory not found'));
                return false;
            }
            $filename = md5(rand()) . "." . $this->file->extension;
            // check if file extension is allowed

            if (in_array($this->file->extension, self::$allowExtensions)) {
                if ($this->file->saveAs($path . '/' . $filename)) {
                    $this->name = $filename;
                    $this->save();
                    return true;
                }
            }else{
                return false;
            }
        }
        return false;
    }

    public function deleteFile($filename)
    {
        $path = realpath(Yii::$app->basePath . Yii::$app->setting->getValue('app::data'));
        if (file_exists($path . '/' . $filename)) {
            unlink($path . '/' . $filename);
        }
    }

}
