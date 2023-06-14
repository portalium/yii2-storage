<?php

use portalium\bootstrap5\Modal;
use portalium\widgets\Pjax;
use yii\helpers\Html;
use kartik\file\FileInput;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;
use portalium\storage\widgets\FilePicker;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="storage-form">

    <?php
        $id = null;
        $widgetName = null;
        if ($isPicker){
            if(isset($model->isNewRecord) && $model->isNewRecord){
                $id = 'file-form-modal-' . $widgetName;
            }else{
                $id = 'file-form-modal-' . $model->id_storage;
            }
        }else{
            
        }
    ?>
   
    <?php 
        if ($isPicker)
        {
            Modal::begin([
                'id' => (isset($model->isNewRecord) && $model->isNewRecord) ? 'file-form-modal-' . $widgetName : 'file-form-modal-' . $model->id_storage,
                'size' => Modal::SIZE_DEFAULT,
                'closeButton' => false,
                'title' => (isset($model->isNewRecord) && $model->isNewRecord) ? Module::t('Create Media') : $model->title,
                'footer' => 
                    (isset($model->isNewRecord) && $model->isNewRecord) ?  Html::button(Module::t('Save'), ['class' => 'btn btn-success', 'data-bs-dismiss' => 'modal', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#file-picker-modal' . $widgetName]) :
                    Html::button(Module::t('Update'), ['class' => 'btn btn-primary', 'data-bs-dismiss' => 'modal', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#file-picker-modal' . $widgetName])
            ]);
        }else
        {
            Modal::begin([
                'id' => (isset($model->isNewRecord) && $model->isNewRecord) ? 'file-form-modal-' : 'file-form-modal-' . $model->id_storage,
                'size' => Modal::SIZE_DEFAULT,
                'closeButton' => false,
                'title' => (isset($model->isNewRecord) && $model->isNewRecord) ? Module::t('Create Media') : $model->title,
                'footer' => 
                    (isset($model->isNewRecord) && $model->isNewRecord) ?  Html::button(Module::t('Save'), ['class' => 'btn btn-success',  'data-bs-dismiss' => 'modal', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#file-picker-modal' . $widgetName]) :
                    Html::button(Module::t('Update'), ['class' => 'btn btn-primary',  'data-bs-dismiss' => 'modal', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#file-picker-modal' . $widgetName])
            ]);
        }
        
    ?>
        <?php 
            if (!$isPicker)
            {
                Pjax::begin([
                    'id' => (isset($model->isNewRecord) && $model->isNewRecord) ? 'file-form-pjax' : 'file-form-pjax-' . $model->id_storage,
                ]);
            }
            else
            {
                Pjax::begin([
                    'id' => (isset($model->isNewRecord) && $model->isNewRecord) ? 'file-form-pjax'. $widgetName : 'file-form-pjax-' . $model->id_storage,
                ]);
            }

        ?>
            <?php 
                $form = ActiveForm::begin([
                    'id' => (isset($model->isNewRecord) && $model->isNewRecord) ? 'file-form' : 'file-form-' . $model->id_storage,
                    'options' => [
                        'data-pjax' => true,
                        'enctype' => 'multipart/form-data',
                    ],
                    'action' => (isset($model->isNewRecord) && $model->isNewRecord) ? '/storage/browser/create' : '/storage/browser/update?id=' . $model->id_storage,
                ]); 
            ?>
                <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'file['. $model->id_storage .']')->widget(FileInput::className(
                )); ?>
            <?php ActiveForm::end(); ?>
        <?php Pjax::end(); ?>
    <?php Modal::end(); ?>

    <?php 
        /* $js = '
        $(document).ready(function() {
            $("#' . (isset($model->isNewRecord) && $model->isNewRecord) . ').on("pjax:success", ' . ((isset($model->isNewRecord) && $model->isNewRecord) ? '"#file-form-pjax"' : '"#file-form-pjax-' . $model->id_storage . '"') . ', function(event) {
                $("#' . ((isset($model->isNewRecord) && $model->isNewRecord) ? 'file-form-modal' : 'file-form-modal-' . $model->id_storage) . '").modal("hide");
                console.log("pjax:success ' . ((isset($model->isNewRecord) && $model->isNewRecord) ? '#file-form-pjax' : '#file-form-pjax-' . $model->id_storage . '') . '");
            });
        });

            
        ';
        $this->registerJs($js); */
    ?>
</div>
