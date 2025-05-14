<?php

use portalium\storage\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Modal;
use yii\helpers\Html;

/* @var $model portalium\storage\models\StorageDirectory */
/* @var $form yii\widgets\ActiveForm */
/* @var $this yii\web\View */

if (isset($model)) {
    Modal::begin([
        'id' => 'renameFolderModal',
        'title' => Module::t('Rename Folder'),
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
                    'id' => 'renameFolderButton',
                    'type' => 'button',
                    "data-id" => $model->id_directory
                ],
            ]),
        'dialogOptions' => ['class' => 'modal-dialog-centered']
    ]);

    $form = ActiveForm::begin([
        'id' => 'renameFolderForm',
        'options' => ['enctype' => 'multipart/form-data', 'data-pjax' => true],
        'method' => 'post',
        'action' => ['/storage/default/rename-folder'],
    ]);

    echo $form->field($model, 'name')->textInput([
        'maxlength' => true,
        'placeholder' => Module::t('Enter new folder name')
    ]);
    ActiveForm::end();
    Modal::end();
} else {
    Yii::$app->session->setFlash('error', Module::t('Folder not found!'));
}