<?php

use portalium\storage\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Modal;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\StorageDirectory */
/* @var $form yii\widgets\ActiveForm */

Modal::begin([
    'id' => 'newFolderModal',
    'title' => Module::t('Create New Folder'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'closeButton' => false,
    'footer' =>
        Button::widget([
            'label' => Module::t('Close'),
            'options' => [
                'class' => 'btn btn-danger',
                'data-bs-dismiss' => 'modal'
            ],
        ]) . ' ' .
        Button::widget([
            'label' => Module::t('Create'),
            'options' => [
                'class' => 'btn btn-success',
                'id' => 'createFolderButton',
                'type' => 'button',
            ],
        ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered']
]);

$form = ActiveForm::begin([
    'id' => 'newFolderForm',
    'options' => ['enctype' => 'multipart/form-data', 'data-pjax' => true],
    'method' => 'post',
    'action' => ['/storage/default/new-folder']
]);
echo $form->field($model, 'name')->textInput(['required' => true])->label(Module::t('Name'));

ActiveForm::end();
Modal::end();
