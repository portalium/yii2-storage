<?php

use portalium\storage\Module;
use portalium\theme\widgets\Dropdown;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\ListView;
use yii\helpers\Url;
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $isPicker bool */

echo ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => function ($model) use ($isPicker) {

        $content = Html::beginTag('span', ['class' => 'file-card col-md-2', 'style' => 'margin-left: 5px; margin-right: 7px;', 'data-id' => $model->id_storage]);
        $cardHeaderStyle = '';
        if ($isPicker)
            $cardHeaderStyle = ' padding-left: 35px;';
        $content .= Html::beginTag('span', ['class' => 'card-header', 'style' => $cardHeaderStyle]);
        if($isPicker)
            $content .= Html::checkbox('selection', false, [
                'class' => 'file-select-checkbox',
                'value' => $model->id_storage,
                'onclick' => 'selectFile(this, ' . $model->id_storage . ')',
            ]);
        $title = $model->title ?: 'Başlık yok';
        $content .= Html::tag('span', Html::encode($title), ['class' => 'file-title ' . ($isPicker ? 'picker' : 'normal')]);
        $content .= Html::tag('i', '', [
            'class' => 'fa fa-ellipsis-h',
            'id' => 'menu-trigger-' . $model->id_storage,
            'data-title' => strtolower($model->title ?: 'Başlık yok'),
            'onclick' => 'toggleContextMenu(event, ' . $model->id_storage . ')'
        ]);
        $content .= Dropdown::widget([
            'items' => [
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-download']) . ' ' . Module::t('Download'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'downloadFile(' . $model->id_storage . '); return false;',
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-pencil']) . ' ' . Module::t('Rename'),
                    'encode' => false,
                    'url' => '#',
                    'linkOptions' => [
                        'onclick' => 'openRenameModal(' . $model->id_storage . ')'
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-refresh']) . ' ' . Module::t('Update'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'openUpdateModal(' . $model->id_storage . ')'
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
                        'onclick' => 'openShareModal(' . $model->id_storage . ')',
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-copy']) . ' ' . Module::t('Make a Copy'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'copyFile(' . $model->id_storage . '); return false;'
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'deleteFile(' . $model->id_storage . '); return false;',
                    ],
                ],
            ],
            'options' => [
                'class' => 'custom-dropdown-menu',
                'id' => 'context-menu-' . $model->id_storage
            ],
        ]);

        $content .= Html::endTag('span');
        $iconUrlData = $model->getIconUrl();
        $content .= Html::img($iconUrlData['url'], [
            'alt' => $model->title,
            'class' => 'file-icon ' . $iconUrlData['class']
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
