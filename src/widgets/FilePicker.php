<?php

namespace portalium\storage\widgets;

use Yii;

use portalium\theme\widgets\Html;
use portalium\storage\models\Storage;
use portalium\theme\widgets\InputWidget;

class FilePicker extends InputWidget
{

    public $dataProvider;
    public $selected;
    public $multiple = 0;
    public $attributes = ['id_storage'];
    public $isJson = 1;
    public $name = '';

    public function init()
    {
        parent::init();
        
        $this->name = $this->generateHtmlId($this->name);

        $this->options['id'] = 'file-picker-input-' . $this->name;
        if (isset($this->options['multiple'])) {
            $this->multiple = $this->options['multiple'];
        }
        if (isset($this->options['attributes'])) {
            $this->attributes = $this->options['attributes'];
        }
        if (isset($this->options['isJson'])) {
            $this->isJson = $this->options['isJson'];
        }
    }

    public function run()
    {

        $this->dataProvider = new \yii\data\ActiveDataProvider([
            'query' => Storage::find(),
            'pagination' => false
        ]);

        $isPicker = true;


        
        if ($this->hasModel()) {
            $input = 'activeHiddenInput';
            echo Html::$input($this->model, $this->attribute, $this->options);
        }

        $model = new Storage();

        if($id_storage = Yii::$app->request->get('id'))
            $model = Storage::findOne($id_storage);

        
        echo $this->renderFile('@vendor/portalium/yii2-storage/src/views/web/browser/index.php', [
            'dataProvider' => $this->dataProvider,
            'attributes' => $this->attributes,
            'isJson' => $this->isJson,
            'widgetName' => $this->name,
            'isPicker' => $isPicker,
            'inputModel' => $this->model,
            'attribute' => $this->attribute,
            'multiple' => $this->multiple,
            'model' => $model,
        ]);
    }

    function generateHtmlId($name) {
        $name = preg_replace('/[^a-zA-Z0-9]+/', ' ', $name);
        $name = str_replace(' ', '-', strtolower(trim($name)));
        return $name;
    }
    
}