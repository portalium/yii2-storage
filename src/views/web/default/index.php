<?php

use yii\helpers\Url;
use yii\helpers\Html;
use portalium\theme\widgets\GridView;
use yii\grid\ActionColumn;
use portalium\storage\Module;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;
use portalium\storage\widgets\FilePicker;
/* @var $this yii\web\View */
/* @var $searchModel portalium\storage\models\StorageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Module::t('Storages');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="storage-index">

    <?php Panel::begin([
    'title' => Module::t('Media List'),
    'actions' => [
        'header' => [
            Html::a(Module::t('Create Storage'), ['create'], ['class' => 'btn btn-success']) 
        ]
    ]
]) ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'title',
            'name',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Storage $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id_storage' => $model->id_storage]);
                 }
            ],
        ],
    ]); ?>
    <?php Panel::end() ?>


</div>
