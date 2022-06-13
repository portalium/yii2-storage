<?php

use yii\helpers\Html;
use diginova\storage\Module;
/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */

$this->title = Module::t('Update Storage: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Module::t('Storages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id_storage' => $model->id_storage]];
$this->params['breadcrumbs'][] = Module::t('Update');
?>
<div class="storage-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
