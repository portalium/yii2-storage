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
    public $manage = false;

    public function init()
    {

        parent::init();
        Yii::$app->view->registerJs('$.pjax.defaults.timeout = 30000;');
        $this->name = $this->generateHtmlId($this->name);
        $this->options['id'] = 'file-picker-input-' . $this->name;
        $this->options['id'] = 'file-picker-input-' . $this->name;

        $attribute = $this->attribute;
        try {
            if (str_contains($attribute, "[")) {
                $startPos = strpos($attribute, "[");
                $endPos = strpos($attribute, "]");

                $attribute = substr($attribute, 0, $startPos) . substr($attribute, $endPos + 1);
            }
        } catch (\Exception $e) {
            // do nothing
        }
        if ($attribute) {
            try {
                $value = json_decode($this->model[$attribute], true);
                if (isset($value['id_storage'])) {
                    $storageModelForName = Storage::findOne($value['id_storage']);
                    $this->options['data-src'] = $storageModelForName ? $storageModelForName->id_storage : null;
                } else if (isset($value['name'])) {
                    $storageModelForName = Storage::findOne($value['name']);
                    $this->options['data-src'] = $storageModelForName ? $storageModelForName->id_storage : null;
                } else {
                    $storageModelForName = Storage::findOne($this->model[$attribute]);
                    $this->options['data-src'] = $storageModelForName ? $storageModelForName->id_storage : '';
                }
            } catch (\Exception $e) {
                // do nothing
            }
        }

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
        if (isset($this->options['callbackName'])) {
            $this->callbackName = $this->options['callbackName'];
        }
    }

    public function run()
    {
        $query = Storage::find();
        if ($this->fileExtensions) {
            foreach ($this->fileExtensions as $fileExtension) {
                $query->orWhere(['like', 'name', $fileExtension]);
            }
        }
        if ($this->manage && isset(Yii::$app->request->queryParams['StorageSearch']['id_workspace']) && Yii::$app->request->queryParams['StorageSearch']['id_workspace'] != '' && Yii::$app->request->queryParams['StorageSearch']['id_workspace'] != null) {
            $query->andWhere(['id_workspace' => Yii::$app->request->queryParams['StorageSearch']['id_workspace']]);
        }
        if ($this->manage && isset(Yii::$app->request->queryParams['StorageSearch']['access']) && Yii::$app->request->queryParams['StorageSearch']['access'] == Storage::ACCESS_PRIVATE) {
            $query->andWhere(['access' => Storage::ACCESS_PRIVATE]);
            $query->andWhere(['like', 'title', Yii::$app->request->queryParams['StorageSearch']['title']]);
        } else if ($this->manage && isset(Yii::$app->request->queryParams['StorageSearch']['access']) && Yii::$app->request->queryParams['StorageSearch']['access'] == Storage::ACCESS_PUBLIC) {
            $query->andWhere(['access' => Storage::ACCESS_PUBLIC]);
            $query->andWhere(['like', 'title', Yii::$app->request->queryParams['StorageSearch']['title']]);
        } else if ($this->manage && isset(Yii::$app->request->queryParams['StorageSearch']['access']) && Yii::$app->request->queryParams['StorageSearch']['access'] == '') {
            $query->andWhere(['like', 'title', Yii::$app->request->queryParams['StorageSearch']['title']]);
        } else if ((!isset($this->manage) || !$this->manage || $this->isPicker) && isset(Yii::$app->request->queryParams['StorageSearch']['access']) && Yii::$app->request->queryParams['StorageSearch']['access'] == Storage::ACCESS_PRIVATE) {
            $query->andWhere(['id_workspace' => Yii::$app->workspace->id]);
            $query->andWhere(['access' => Storage::ACCESS_PRIVATE]);
            $query->andWhere(['like', 'title', Yii::$app->request->queryParams['StorageSearch']['title']]);
        } else if ((!isset($this->manage) || !$this->manage || $this->isPicker) && isset(Yii::$app->request->queryParams['StorageSearch']['access']) && Yii::$app->request->queryParams['StorageSearch']['access'] == Storage::ACCESS_PUBLIC) {
            $query->andWhere(['like', 'title', Yii::$app->request->queryParams['StorageSearch']['title']]);
            $query->andWhere(['access' => Storage::ACCESS_PUBLIC])->andWhere(['id_workspace' => Yii::$app->workspace->id]);
        } else if ((!isset($this->manage) || !$this->manage || $this->isPicker) && isset(Yii::$app->request->queryParams['StorageSearch']['access']) && Yii::$app->request->queryParams['StorageSearch']['access'] == '') {
            $query->andWhere(['like', 'title', Yii::$app->request->queryParams['StorageSearch']['title']]);
            $query->andWhere([
                'OR',
                ['and', ['id_workspace' => Yii::$app->workspace->id, 'access' => Storage::ACCESS_PRIVATE]],
                ['access' => Storage::ACCESS_PUBLIC, 'id_workspace' => Yii::$app->workspace->id]
            ]);
        } else if ((!isset($this->manage) || !$this->manage || $this->isPicker) && (!isset(Yii::$app->request->queryParams['StorageSearch']['access']) || !isset(Yii::$app->request->queryParams['StorageSearch']['title']))) {
            $query->andWhere([
                'OR',
                ['and', ['id_workspace' => Yii::$app->workspace->id, 'access' => Storage::ACCESS_PRIVATE]],
                ['access' => Storage::ACCESS_PUBLIC, 'id_workspace' => Yii::$app->workspace->id]
            ]);
        } else if ((isset($this->manage) && $this->manage) && (!isset(Yii::$app->request->queryParams['StorageSearch']['access']) || !isset(Yii::$app->request->queryParams['StorageSearch']['title']))) {
        } else {
            $query->andWhere([
                1 => 0
            ]);
        }
        $searchModel = new \portalium\storage\models\StorageSearch();
        $this->dataProvider = new \portalium\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $this->isPicker ? 1 : (Yii::$app->session->get('theme::page_size') ? Yii::$app->session->get('theme::page_size') : 12),
            ],
            'sort' => [
                'defaultOrder' => [
                    'id_storage' => SORT_DESC,
                ]
            ],
        ]);
        if ($this->isPicker) {
            if ($this->dataProvider->getCount() > 0) {
                $this->dataProvider->setModels([array_values($this->dataProvider->getModels())[0]]);
            }
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
            'searchModel' => $searchModel,
            'manage' => $this->manage,
        ]);
    }

    function generateHtmlId($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9]+/', ' ', $name);
        $name = str_replace(' ', '-', strtolower(trim($name)));
        return $name;
    }
}
