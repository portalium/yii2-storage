<?php

use yii\helpers\Html;
use portalium\theme\widgets\FileInput;
use yii\widgets\ActiveForm;
use portalium\storage\Module;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;
use portalium\storage\widgets\FilePicker;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="storage-form">

    <?php $form = ActiveForm::begin(); ?>
    <?php Panel::begin([
    'title' => ($model->isNewRecord) ? Module::t('Create Media') : $model->title,
    'actions' => [
        'header' => [

        ],
        'footer' => [
            (!$model->isNewRecord) ?  Html::submitButton(Module::t('Save'), ['class' => 'btn btn-primary']) : 
            Html::submitButton(Module::t('Upload'), ['class' => 'btn btn-primary'])
        ]
    ]
]) ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'file')->fileInput() ?>



    <?php Panel::end() ?>

    <?php ActiveForm::end(); ?>

   

</div>
