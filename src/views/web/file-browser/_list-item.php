<?php

use yii\helpers\Url;
use yii\web\View;
use portalium\widgets\Pjax;
use portalium\widgets\ListView;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Panel;
$this->registerCss(
    <<<CSS
    .storage-item {
        cursor: pointer;
        padding: 10px;
    }
    .storage-item:hover {
        background-color: #f5f5f5;
    }
    
    .storage-item.selected-item {
        background-color: #78D56E;
    }
    CSS
);
$viewParams = $isPicker ? [
    'isModal' => Storage::IS_MODAL_TRUE,
    'attributes' => $attributes,
    'isJson' => $isJson,
    'widgetName' => $name,
    'multiple' => $multiple,
    'callbackName' => $callbackName,
    'isPicker' => $isPicker,
    'fileExtensions' => $fileExtensions,
] : [
    'isModal' => Storage::IS_MODAL_TRUE,
    'isJson' => $isJson,
    'widgetName' => $name,
    'isPicker' => $isPicker,
    'fileExtensions' => $fileExtensions,
];
echo <<<HTML
    <div class="d-flex flex-column flex-row-auto">
                <div class="d-flex flex-column-fluid flex-center">
HTML;

echo ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => '_file',
    'viewParams' => $viewParams,
    'options' => [
        'tag' => 'div',
        'class' => 'row',
        'style' => 'overflow-y: auto; height: calc(100vh - 316px);',
    ],
    'itemOptions' => $isPicker ?
        function ($model, $key, $index, $widget) use ($attributes, $isJson, $name) {
            if (isset($attributes)) {
                if (is_array($attributes)) {
                    if (in_array('id_storage', $attributes)) {
                    } else {
                        $attributes[] = 'id_storage';
                    }
                }
            }
            return [
                'tag' => 'div',
                'class' => 'col-lg-2 col-sm-3 col-md-2 storage-item',
                'data' => ($isJson == 1) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]],
                //'onclick' => 'selectItem(this, "' . $name . '")',
            ];
        } :
        function ($model, $key, $index, $widget) use ($isJson, $name) {
            return
                [
                    'tag' => 'div',
                    'class' => 'col-lg-2 col-sm-3 col-md-2 storage-item',
                    //'onclick' => 'selectItem(this, "' . $name . '")',
                    'data' => ($isJson == 1) ? json_encode($model->getAttributes(['id_storage'])) : $model->getAttributes(['id_storage'])['id_storage'],
                ];
        },
    'summary' => false,
    'layout' => '{items}{pager}<div class="clearfix"></div>',

]);
echo <<<HTML
    </div>
    </div>
HTML;
