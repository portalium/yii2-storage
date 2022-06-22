<?php

use yii\helpers\Html;
use kartik\file\FileInput;
use portalium\storage\Module;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="storage-form">


    <label class="control-label"><?= Module::t('Title') ?></label>
    <?= Html::textInput('title', $model->title, ['class' => 'form-control', 'id' => 'storage-title']) ?>
    <label class="control-label"><?= Module::t('File') ?></label>
    <?= FileInput::widget(
        [
            'name' => 'attachment_50',
            'attribute' => 'file',
            'id' => 'storage-file',
            'pluginOptions' => [
                'showPreview' => false,
                'showCaption' => true,
                'showRemove' => true,
                'showUpload' => false
            ]
        ],
        ['class' => 'form-control']
    ) ?>

    <?php
        echo Html::beginTag('div', ['id' => 'view-file']);
        if (!$model->isNewRecord) {
            echo $this->render('_file', ['model' => $model, 'view' => 0, 'returnAttribute' => []]);
        }
        echo Html::endTag('div');
    ?>
</div>