<?php

use portalium\storage\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Modal;
use yii\helpers\Html;
/* @var $model portalium\storage\models\Storage */

if (isset($model)) {
    Modal::begin([
        'id' => 'updateModal',
        'title' => Module::t('Update'),
        'options' => ['class' => 'fade'],
        'bodyOptions' => ['class' => 'modal-body'],
        'clientOptions' => [
            'backdrop' => 'static',
            'keyboard' => false,
        ],
        'footer' => Button::widget([
            'label' => Module::t('Close'),
            'options' => [
                'class' => 'btn btn-danger',
                'onclick' => 'hideModal("updateModal")',
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
        'options' => ['enctype' => 'multipart/form-data', 'data-pjax' => true],
        'method' => 'post'
    ]);

    echo $form->field($model, 'file')->fileInput(['accept' => 'image/*']);

    ActiveForm::end();
    Modal::end();
} else
    Yii::$app->session->setFlash('error', Module::t('File not found!'));
