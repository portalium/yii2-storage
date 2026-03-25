<?php

use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Modal;
use yii\helpers\Html;

/**
 * @var array $ids       Array of item IDs to move
 * @var array $directoryTree  Flat list of ['model' => Storage, 'level' => int]
 */

Modal::begin([
    'id' => 'moveItemsModal',
    'title' => Module::t('Move Items'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'clientOptions' => [
        'backdrop' => 'static',
        'keyboard' => false,
    ],
    'footer' =>
        Html::hiddenInput('move-target-directory', '', ['id' => 'moveTargetDirectory']) .
        Button::widget([
            'label' => Module::t('Close'),
            'options' => [
                'class' => 'btn btn-danger',
                'onclick' => 'hideModal("moveItemsModal")',
            ],
        ]) . ' ' .
        Button::widget([
            'label' => Module::t('Move Here'),
            'options' => [
                'class' => 'btn btn-success',
                'id' => 'confirmMoveBtn',
                'data-ids' => json_encode($ids),
            ],
        ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
]);

echo Html::tag('p', Module::t('Select the destination folder:'), ['class' => 'text-muted mb-2']);

echo Html::beginTag('div', ['class' => 'move-directory-tree', 'id' => 'moveDirectoryTree']);

echo Html::tag(
    'div',
    Html::tag('i', '', ['class' => 'fa fa-home me-2']) . Module::t('Root (Home)'),
    [
        'class' => 'move-dir-item active',
        'data-id' => '',
        'onclick' => 'selectMoveTarget(this, "")',
    ]
);

foreach ($directoryTree as $entry) {
    $model = $entry['model'];
    $level = $entry['level'];
    $padding = ($level * 20) + 10;

    echo Html::tag(
        'div',
        Html::tag('i', '', ['class' => 'fa fa-folder me-2']) . Html::encode($model->name),
        [
            'class' => 'move-dir-item',
            'data-id' => $model->id_storage,
            'style' => 'padding-left: ' . ($padding + 12) . 'px;',
            'onclick' => 'selectMoveTarget(this, ' . $model->id_storage . ')',
        ]
    );
}

if (empty($directoryTree)) {
    echo Html::tag('div', Module::t('No other folders available.'), ['class' => 'text-muted ps-2 mt-2']);
}

echo Html::endTag('div');

Modal::end();
