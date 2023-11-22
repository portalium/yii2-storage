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

    public $dataProvider;
    public $selected;
    public $multiple = 0;
    public $attributes = ['id_storage'];

    public $name = '';

    public $isJson = 1;

    public $isPicker = true;

    public $callbackName = null;

    public $fileExtensions = null;

    public function init()
    {
        parent::init();
        Yii::$app->view->registerJs('$.pjax.defaults.timeout = 30000;');
        $this->name = $this->generateHtmlId($this->name);
        $this->options['id'] = 'file-picker-input-' . $this->name;
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
        if (isset($this->options['isPicker'])) {
            $this->isPicker = $this->options['isPicker'];
        }

        if (isset($this->options['fileExtensions'])) {
            $this->fileExtensions = $this->options['fileExtensions'];
        }
    }

    public function run()
    {
        $query = Storage::find();
        if ($this->fileExtensions) {
            foreach ($this->fileExtensions as $fileExtension) {
                $query->orWhere(['like', 'name', $fileExtension]);
            }
            Yii::warning($query->createCommand()->getRawSql());
        }
        $this->dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $this->isPicker ? 1 : 12,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id_storage' => SORT_DESC,
                ]
            ],
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
        
        
        echo $this->renderFile('@vendor/portalium/yii2-storage/src/views/web/file-browser/index.php', [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'multiple' => $this->multiple,
            'dataProvider' => $this->dataProvider,
            'isJson' => $this->isJson,
            'storageModel' => $model,
            'attributes' => $this->attributes,
            'name' => $this->name,
            'callbackName' => $this->callbackName,
            'isPicker' => $this->isPicker,
            'fileExtensions' => $this->fileExtensions,
        ]);
    }

    function generateHtmlId($name) {

        $name = preg_replace('/[^a-zA-Z0-9]+/', ' ', $name);

        $name = str_replace(' ', '-', strtolower(trim($name)));
    
        return $name;
    }
    
}