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
    public function init()
    {
        parent::init();
        $this->options['id'] = 'file-picker-input';

    }

    public function run()
    {

        if (Yii::$app->user->can('storageWidgetFilePickerAllShowFile')){
            $this->files = new \yii\data\ActiveDataProvider([
                'query' => Storage::find(),
            ]);
        }else{
            $this->files = new \yii\data\ActiveDataProvider([
                'query' => Storage::find()->where(['id_user' => Yii::$app->user->id]),
            ]);
        }
        
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
            'files' => $this->files,
            'storageModel' => $model,
            'returnAttribute' => $this->returnAttribute,
        ]);
    }
}