<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use diginova\storage\Module;
use portalium\theme\widgets\Panel;
/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Module::t('Storages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="storage-view">
    <?php Panel::begin([
        'title' => Html::encode($this->title),
        'actions' => [
            'header' => [
                Html::a(Module::t( ''), ['update', 'id' => $model->id_storage], ['class' => 'fa fa-pencil btn btn-primary']),
                Html::a(Module::t( ''), ['delete', 'id' => $model->id_storage], [
                    'class' => 'fa fa-trash btn btn-danger',
                    'data' => [
                        'confirm' => Module::t( 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]),
            ]
        ]
    ]) ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_storage',
            'name',
            'title',
        ],
    ]) ?>

    <?php
    Panel::end()
    ?>

</div>
