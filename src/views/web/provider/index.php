<?php

use portalium\storage\Module;
use portalium\theme\widgets\Panel;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Module::t('Storage Providers');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="storage-provider-index">

    <?php Panel::begin([
        'title' => Module::t('Storage Providers'),
        'actions' => [
            Html::a('', ['create'], ['class' => 'fa fa-plus btn btn-success'])
        ]
    ]) ?>

    <?= $this->render('_grid', [
        'dataProvider' => $dataProvider,
        'showScope' => false,
    ]) ?>

    <?php Panel::end() ?>
</div>
