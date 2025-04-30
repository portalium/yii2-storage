<?php

use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Dropdown;
use portalium\theme\widgets\ListView;

/* @var $dataProvider yii\data\ActiveDataProvider */

echo ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => function ($model) {
        $folderId = $model->id_directory;
        $folderName = Html::encode($model->name);

        $content = Html::beginTag('div', [
            'class' => 'folder-container',
            'onclick' => "openFolder($folderId)"
        ]);

        $content .= Html::tag('i', '', [
            'class' => 'fa fa-ellipsis-h folder-ellipsis',
            'onclick' => "toggleFolderMenu(event, $folderId)",
        ]);

        $content .= Dropdown::widget([
            'items' => [
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-pencil']) . ' ' . Module::t('Rename'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'openRenameFolderModal(' . $folderId . ')'
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-arrows-alt']) . ' ' . Module::t('Move'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'openMoveFolderModal(' . $folderId . ')'
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-share-alt']) . ' ' . Module::t('Share'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'openShareFolderModal(' . $folderId . ')'
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => 'deleteFolder(' . $folderId . '); return false;'
                    ],
                ],
            ],
            'options' => [
                'class' => 'folder-dropdown-menu',
                'id' => 'context-folder-menu-' . $folderId,
            ],
        ]);

        $content .= Html::tag('div', '', ['class' => 'folder']);
        $content .= Html::tag('div', '', ['class' => 'folder-notch']);
        $content .= Html::tag('div', $folderName, ['class' => 'folder-title']);

        $content .= Html::endTag('div');

        return $content;
    },
    'options' => [
        'id' => 'folder-list-container',
    ],
    'itemOptions' => [
        'tag' => false,
    ],
    'layout' => "{items}",
]);
