<?php

use portalium\storage\Module;
use portalium\theme\widgets\Dropdown;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\ListView;
use yii\helpers\Url;
/* @var $dataProvider yii\data\ActiveDataProvider */


echo ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => function ($model) {

        $content = Html::beginTag('span', ['class' => 'file-card col-md-2', 'style' => 'margin-left: 5px; margin-right: 7px;']);
        $content .= Html::beginTag('span', ['class' => 'card-header']);
        $content .= $model->title ?: 'Başlık yok';
        $content .= Html::tag('i', '', [
            'class' => 'fa fa-ellipsis-h',
            'id' => 'menu-trigger-' . $model->id_storage,
        ]);
        $content .= Dropdown::widget([
            'items' => [
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-download']) . ' ' . Module::t('Download'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'downloadItem(this); return false;',
                        'download-url' => Url::to(['/storage/default/get-file', 'id' => $model->id_storage]),
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-pencil']) . ' ' . Module::t('Rename'),
                    'encode' => false,
                    'url' => '#',
                    'linkOptions' => [
                        'onclick' => 'openRenameModal(' . $model->id_storage . ')',
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-refresh']) . ' ' . Module::t('Update'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#updateModal',
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-arrows-alt']) . ' ' . Module::t('Move'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-share-alt']) . ' ' . Module::t('Share'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#shareModal',
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-copy']) . ' ' . Module::t('Make a Copy'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'data-id' => $model->id_storage,
                        'onclick' => 'copyFile(this); return false;',
                        'class' => 'make-copy-btn'
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'removeItem(this); return false;',
                        'data-id' => $model->id_storage,
                        'data-url' => Url::to(['/storage/default/delete']),
                        'class' => 'btn-delete',
                    ],
                ],
            ],
            'options' => [
                'class' => 'dropdown-menu',
                'id' => 'context-menu-' . $model->id_storage,
            ],
        ]);

        $content .= Html::endTag('span');
        $content .= Html::img($model->getIconUrl(), [
            'alt' => $model->title,
            'class' => 'file-icon',
        ]);

        $content .= Html::endTag('span');

        return $content;
    },
    'options' => [
        'class' => 'files-container row',
    ],
    'itemOptions' => [
        'tag' => false,
    ],
    'layout' => "{items}\n{pager}",
]);

