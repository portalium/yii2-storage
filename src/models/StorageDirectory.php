<?php

namespace portalium\storage\models;

use Yii;

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
            [['date_create', 'date_update'], 'safe'],
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

}
