<?php

use portalium\storage\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Modal;
use yii\helpers\Html;

/* @var $model portalium\storage\models\Storage */


if (isset($model)) {
    Modal::begin([
        'id' => 'renameModal',
        'title' => Module::t('Rename'),
        'options' => ['class' => 'fade'],
        'bodyOptions' => ['class' => 'modal-body'],
        'closeButton' => false,
        'footer' => Button::widget([
                'label' => Module::t('Close'),
                'options' => [
                    'class' => 'btn btn-danger',
                    'data-bs-dismiss' => 'modal'
                ],
            ]) . ' ' . Button::widget([
                'label' => Module::t('Rename'),
                'options' => [
                    'class' => 'btn btn-success',
                    'id' => 'renameButton',
                    'type' => 'button',
                ],
            ]),
        'dialogOptions' => ['class' => 'modal-dialog-centered']
    ]);

    $form = ActiveForm::begin([
        'id' => 'renameForm',
        'options' => ['enctype' => 'multipart/form-data', 'data-pjax' => true],
        'method' => 'post'
    ]);

    echo $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => Module::t('Enter new name')]);

    ActiveForm::end();
    Modal::end();
}
else
    Yii::$app->session->setFlash('error', Module::t('File not found!'));
