<?php

use portalium\storage\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;

/* @var $model portalium\storage\models\Storage */

Modal::begin([
    'id' => 'updateModal',
    'title' => Module::t('Update'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'footer' => Button::widget([
            'label' => Module::t('Close'),
            'options' => [
                'class' => 'btn btn-danger',
                'data-bs-dismiss' => 'modal',
            ],
        ]) . ' ' . Button::widget([
            'label' => Module::t('Update'),
            'options' => [
                'class' => 'btn btn-success',
                'id' => 'updateButton',
                'type' => 'button',
            ],
        ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered']
]);

$form = ActiveForm::begin([
    'id' => 'updateForm',
    'options' => ['enctype' => 'multipart/form-data'],
]);

if (isset($model)) {
    echo $form->field($model, 'file')->fileInput(['accept' => 'image/*']);
}

ActiveForm::end();

Modal::end();
