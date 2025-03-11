<?php

use portalium\bootstrap5\Modal;
use yii\helpers\Html;
use portalium\storage\models\Storage;
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
        <label class="col-sm-2 col-form-label" for="storage-access<?= $widgetName ?>"><?= Module::t('Access') ?></label>
        <div class="col-sm-10">
            <?= Html::dropDownList('access', $model->access ? $model->access : Storage::ACCESS_PRIVATE, \portalium\storage\models\Storage::getAccesses(), ['class' => 'form-control', 'id' => 'storage-access' . $widgetName]) ?>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-2 col-form-label" for="storage-file<?= $widgetName ?>">
            <?= Module::t('File') ?>
        </label>
        <div class="col-sm-10">
            <div class="input-group">
                <label class="input-group-text" for="storage-file<?= $widgetName ?>">
                    <?= Module::t('Select File') ?>
                </label>
                <input type="file" id="storage-file<?= $widgetName ?>" class="form-control d-none"
                    onchange="document.getElementById('file-name-<?= $widgetName ?>').textContent = this.files.length ? this.files[0].name : '<?= Module::t('No file selected') ?>'">
                <span id="file-name-<?= $widgetName ?>" class="form-control">
                    <?= Module::t('No file selected') ?>
                </span>
            </div>
        </div>
    </div>

    <?php
        Modal::begin([
            'id' => 'storage-error-modal' . $widgetName,
            'title' => Module::t('Error'),
            'footer' => '<a href="#" class="btn btn-primary" data-bs-dismiss="modal">' . Module::t('Close') . '</a>',
            'size' => 'modal-sm',
            'clientOptions' => ['backdrop' => 'static', 'keyboard' => false]
        ]);
        echo Html::tag('div', '', ['id' => 'storage-error' . $widgetName, 'class' => 'help-block float-start', 'style' => 'color:red;']);
        Modal::end();
    ?>
    <?php
        echo Html::beginTag('div', ['id' => 'view-file']);
        if (!$model->isNewRecord) {
            echo $this->render('_file', ['model' => $model, 'isModal' => Storage::IS_MODAL_FALSE, 'attributes' => [], 'isPicker' => $isPicker, 'isJson' => $isJson, 'widgetName' => $widgetName, 'multiple' => $multiple, 'callbackName' => $callbackName, 'fileExtensions' => $fileExtensions]);
        }
        echo Html::endTag('div');
    ?>
</div>