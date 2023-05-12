<?php

use yii\helpers\Html;
use kartik\file\FileInput;
use portalium\storage\Module;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="storage-form">


    <div class="mb-3 row">
        <label class="col-sm-2 col-form-label" for="storage-title<?= $widgetName ?>"><?= Module::t('Title') ?></label>
        <div class="col-sm-10">
            <?= Html::textInput('title', $model->title, ['class' => 'form-control', 'id' => 'storage-title' . $widgetName]) ?>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-2 col-form-label" for="storage-file<?= $widgetName ?>"><?= Module::t('File') ?></label>
        <div class="col-sm-10">
            <?= FileInput::widget(
        [
            'name' => 'attachment_50',
            'attribute' => 'file',
            'id' => 'storage-file' . $widgetName,
            'pluginOptions' => [
                'showPreview' => false,
                'showCaption' => true,
                'showRemove' => true,
                'showUpload' => false
            ]
        ],
        ['class' => 'form-control']
    ) ?>
        </div>
    </div>
    <?php
    
    ?>
    <?php
        //echo error message
        echo Html::tag('div', '', ['id' => 'storage-error' . $widgetName, 'class' => 'help-block float-end', 'style' => 'color:red;']);
    ?>
    <?php
        echo Html::beginTag('div', ['id' => 'view-file']);
        if (!$model->isNewRecord) {
            echo $this->render('_file', ['model' => $model, 'view' => 0, 'returnAttribute' => []]);
        }
        echo Html::endTag('div');
    ?>
</div>