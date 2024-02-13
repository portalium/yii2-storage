<?php

use yii\helpers\Html;
use portalium\storage\Module;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */

$this->title = Module::t('Create Storage');
$this->params['breadcrumbs'][] = ['label' => Module::t('Storage'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="storage-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
