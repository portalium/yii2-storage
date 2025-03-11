<?php

use yii\helpers\Html;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
/* @var $this yii\web\View */
/* @var $model portalium\storage\models\StorageSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="storage-search">

    <?php $form = ActiveForm::begin([
        'action' => ['\storage\file-browser\index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id_storage') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'title') ?>

    <div class="form-group">
        <?= Html::submitButton(Module::t('Search'), ['class' => 'btn btn-primary',]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>