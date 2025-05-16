<?php

use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;

/* @var $dataProvider yii\data\ActiveDataProvider */

Yii::$app->view->registerCss("
    #file-picker-modal .panel-footer {
        border-top: none !important;
    }
");


Modal::begin([
    'title' => Module::t('Select File'),
    'id' => 'file-picker-modal',
    'size' => Modal::SIZE_LARGE,
    'footer' =>
        Html::button(Module::t('Close'), [
            'class' => 'btn btn-danger filepicker-close',
            'data-bs-dismiss' => 'modal',
        ]) .
        Html::button(Module::t('Select'), [
            'class' => 'btn btn-success btn-select',
            'onclick' => 'saveSelect()',
        ]),
]);

echo $this->render('@portalium/storage/views/web/default/index', [
    'fileDataProvider' => $dataProvider,
    'directoryDataProvider' => $directoryDataProvider,  // Veriyi gönderiyoruz

    'isPicker' => true,
]);

Modal::end();
