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
    public $multiple = 0;
    public $returnAttribute = ['id_storage'];
    public $json = 1;
    public $modelIdField = 'id';
    public function init()
    {
        parent::init();
        $this->options['id'] = 'file-picker-input';
        if (isset($this->options['multiple'])) {
            $this->multiple = $this->options['multiple'];
        }
        if (isset($this->options['returnAttribute'])) {
            $this->returnAttribute = $this->options['returnAttribute'];
        }
        if (isset($this->options['json'])) {
            $this->json = $this->options['json'];
        }
        if (isset($this->options['modelIdField'])) {
            $this->modelIdField = $this->options['modelIdField'];
        }
    }

    public function run()
    {


            $this->files = new \yii\data\ActiveDataProvider([
                'query' => Storage::find(),
                'pagination' => false
            ]);

        
        if ($this->hasModel()) {
            $input = 'activeHiddenInput';
            echo Html::$input($this->model, $this->attribute, $this->options);
        }

        $model = new Storage();
        if (Yii::$app->request->isGet) {
            $id_storage = Yii::$app->request->get('id_storage');
            if ($id_storage) {
                $model = Storage::findOne($id_storage);
            }
        }
        
        echo $this->render('./file-picker-modal', [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'multiple' => $this->multiple,
            'json' => $this->json,
            'files' => $this->files,
            'storageModel' => $model,
            'returnAttribute' => $this->returnAttribute,
            'modelIdField' => $this->modelIdField
        ]);
    }
}