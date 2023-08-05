<?php

use yii\helpers\Html;
use portalium\storage\Module;
/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */

$this->title = Module::t('Update Storage: {name}', [
    'name' => $model->title,
]);
$this->params['breadcrumbs'][] = ['label' => Module::t('Storages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id_storage' => $model->id_storage]];
$this->params['breadcrumbs'][] = Module::t('Update');
?>
<div class="storage-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
