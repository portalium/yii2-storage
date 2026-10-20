<?php

use portalium\storage\Module;
use portalium\storage\models\StorageProvider;
use portalium\theme\widgets\ActionColumn;
use portalium\theme\widgets\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $showScope bool True on the admin listing, which spans workspaces */

$showScope = $showScope ?? false;

$systemProviderId = (int) Yii::$app->setting->getValue('storage::system_provider');
?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'portalium\grid\SerialColumn'],
            'name',
            [
                'attribute' => 'type',
                'value' => function ($model) {
                    return $model->getTypeLabel();
                },
            ],
            [
                'attribute' => 'capability',
                'value' => function ($model) {
                    return $model->capability === StorageProvider::CAPABILITY_STORAGE
                        ? Module::t('Storage')
                        : Module::t('Source');
                },
            ],
            // On the workspace listing every row is either the site's or the
            // viewer's own workspace, so naming the workspace adds nothing.
            $showScope ? [
                'attribute' => 'id_workspace',
                'label' => Module::t('Scope'),
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->isSystem()
                        ? '<span class="badge bg-info">' . Module::t('System') . '</span>'
                        : Html::encode($model->workspace->title ?? Module::t('Workspace'));
                },
            ] : [
                'attribute' => 'id_workspace',
                'label' => Module::t('Scope'),
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->isSystem()
                        ? '<span class="badge bg-info">' . Module::t('System') . '</span>'
                        : '<span class="badge bg-secondary">' . Module::t('Workspace') . '</span>';
                },
            ],
            [
                'label' => Module::t('Files'),
                'value' => function ($model) {
                    return $model->getFileCount();
                },
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->isActive()
                        ? '<span class="badge bg-success">' . Module::t('Active') . '</span>'
                        : '<span class="badge bg-warning">' . Module::t('Passive') . '</span>';
                },
            ],
            [
                'attribute' => 'is_default',
                'format' => 'raw',
                'value' => function ($model) use ($systemProviderId) {
                    // Read from the setting, not the column: the site-wide
                    // destination is chosen in Settings, so the stale flag on
                    // the record would otherwise contradict it here.
                    return (int) $model->id_provider === $systemProviderId
                        ? '<i class="fa fa-check text-success"></i>'
                        : '';
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{update} {delete}',
            ],
        ],
    ]); ?>

