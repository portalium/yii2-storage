<?php

use portalium\storage\Module;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\StorageProvider */

$this->title = Module::t('Edit Provider');
$this->params['breadcrumbs'][] = ['label' => Module::t('Storage Providers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="storage-provider-update">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
