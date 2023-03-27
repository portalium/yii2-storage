<?php

namespace portalium\storage\widgets;

use Yii;
use yii\base\Model;
use yii\base\Widget;
use yii\widgets\ListView;
use kartik\file\FileInput;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\storage\models\Storage;
use portalium\theme\widgets\InputWidget;
use portalium\theme\widgets\Modal;

class FilePicker extends InputWidget
{
    public $files;
    public $selected;
    public $multiple = false;
    public $returnAttribute = ['id_storage'];
    public $json = true;
    public $modelIdField = 'id';

    public function init()
    {
        parent::init();
        $this->options['id'] = 'file-picker-input';
        $this->multiple = isset($this->options['multiple']) && $this->options['multiple'];
        $this->returnAttribute = isset($this->options['returnAttribute']) ? $this->options['returnAttribute'] : ['id_storage'];
        $this->json = isset($this->options['json']) && $this->options['json'];
        $this->modelIdField = isset($this->options['modelIdField']) ? $this->options['modelIdField'] : 'id';
    }

    public function run()
    {
        $query = Yii::$app->user->can('storageWidgetFilePickerAllShowFile')
            ? Storage::find()
            : Storage::find()->where(['id_user' => Yii::$app->user->id]);

        $this->files = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        if ($this->hasModel()) {
            echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        }

        $model = new Storage();
        $id_storage = Yii::$app->request->get('id_storage');
        if ($id_storage) {
            $model = Storage::findOne($id_storage);
        }

        echo $this->render('./file-picker-modal', [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'multiple' => $this->multiple,
            'json' => $this->json,
            'files' => $this->files,
            'storageModel' => $model,
            'returnAttribute' => $this->returnAttribute,
            'modelIdField' => $this->modelIdField,
        ]);
    }
}
