<?php

use portalium\storage\models\StorageDirectory;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Dropdown;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var array $directories */
/** @var array $files */
/** @var bool $isPicker */
/** @var \yii\data\Pagination $pagination */

$id_directory = Yii::$app->request->get('id_directory');
$parentDirectory = null;

if ($id_directory !== null) {
    $parentDirectory = StorageDirectory::findOne($id_directory);
}

echo Html::beginTag('div', ['class' => 'container-fluid']);
echo Html::beginTag('div', ['class' => 'row mb-3']);
echo Html::beginTag('div', ['class' => 'col-12']);

if ($id_directory !== null) {
    $parentId = $parentDirectory && $parentDirectory->id_parent ? $parentDirectory->id_parent : null;
    $backUrl = $parentId ? ['index', 'id_directory' => $parentId] : ['index'];
    echo Html::a(
        Html::tag('i', '', ['class' => 'fa fa-chevron-left']) . ' ' ,
        $backUrl,
        ['class' => 'btn btn-lg', 'data-pjax' => true, 'onclick' => 'currentDirectoryId = ' . ($parentId ? $parentId : 'null') . ';']
    );

    $pathItems = [];
    $currentDir = $parentDirectory;

    while ($currentDir !== null) {
        array_unshift($pathItems, [
            'name' => $currentDir->name,
            'id' => $currentDir->id_directory
        ]);

        if ($currentDir->id_parent === null) {
            break;
        }

        $currentDir = StorageDirectory::findOne($currentDir->id_parent);
    }

    echo Html::beginTag('nav', ['class' => 'ml-3 d-inline-block']);
    echo Html::beginTag('ol', ['class' => 'breadcrumb d-inline-flex mb-0']);

    echo Html::tag('li',
        Html::a(Module::t('Home'), ['index'], ['data-pjax' => true, 'onclick' => 'currentDirectoryId = null;']),
        ['class' => 'breadcrumb-item']
    );

    foreach ($pathItems as $i => $item) {
        if ($i === count($pathItems) - 1) {
            echo Html::tag('li', Html::encode($item['name']), ['class' => 'breadcrumb-item active']);
        } else {
            echo Html::tag('li',
                Html::a(Html::encode($item['name']), ['index', 'id_directory' => $item['id']], ['data-pjax' => true, 'onclick' => 'currentDirectoryId = ' . $item['id'] . ';']),
                ['class' => 'breadcrumb-item']
            );
        }
    }

    echo Html::endTag('ol');
    echo Html::endTag('nav');
}

echo Html::endTag('div');
echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'row mb-3']);
echo Html::beginTag('div', ['class' => 'col-12']);
echo Html::endTag('div');
echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'row']);

foreach ($directories as $model) {
    $folderId = $model->id_directory;
    $folderName = Html::encode($model->name);

    $content = Html::beginTag('div', [
        'class' => 'col-md-1 mb-6',
        'id' => 'folder-' . $folderId,
    ]);

    $content .= Html::beginTag('div', [
        'class' => 'folder-container',
        'data-id' => $folderId,
        'onclick' => "openFolder($folderId, event)",
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
                'linkOptions' => ['onclick' => 'openRenameFolderModal(' . $folderId . ')'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-arrows-alt']) . ' ' . Module::t('Move'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openMoveFolderModal(' . $folderId . ')'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-share-alt']) . ' ' . Module::t('Share'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openShareFolderModal(' . $folderId . ')'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => [
                    'onclick' => 'deleteFolder(' . $folderId . '); return false;',
                    'data-id' => $folderId,
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
    $content .= Html::endTag('div');
    echo $content;
}

echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'row mb-3']);
echo Html::beginTag('div', ['class' => 'col-12']);
echo Html::endTag('div');
echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'row']);

foreach ($files as $model) {
    $content = Html::beginTag('div', ['class' => 'col-md-2 col-sm-3 col-6 mb-3']);

    $content .= Html::beginTag('div', ['class' => 'file-card-wrapper']);
    $content .= Html::beginTag('div', [
        'class' => 'file-card',
        'data-id' => $model->id_storage,
        'onclick' => $isPicker ? 'handleFileCardClick.call(this, event, ' . $model->id_storage . ')' : null,
    ]);

    $content .= Html::beginTag('div', ['class' => 'card-header']);

    if ($isPicker) {
        $content .= Html::checkbox('selection', false, [
            'class' => 'file-select-checkbox',
            'value' => $model->id_storage,
            'onclick' => 'selectFile(this, ' . $model->id_storage . ')',
        ]);
    }

    $title = $model->title ?: 'Başlık yok';
    $content .= Html::tag('span', Html::encode($title), ['class' => 'file-title ' . ($isPicker ? 'picker' : 'normal')]);

    $content .= Html::tag('i', '', [
        'class' => 'fa fa-ellipsis-h',
        'id' => 'menu-trigger-' . $model->id_storage,
        'data-title' => $title,
        'onclick' => 'toggleContextMenu(event, ' . $model->id_storage . ')',
    ]);

    $content .= Html::endTag('div');

    $content .= Dropdown::widget([
        'items' => [
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-download']) . ' ' . Module::t('Download'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'downloadFile(' . $model->id_storage . '); return false;'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-pencil']) . ' ' . Module::t('Rename'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openRenameModal(' . $model->id_storage . ')'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-refresh']) . ' ' . Module::t('Update'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openUpdateModal(' . $model->id_storage . ')'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-arrows-alt']) . ' ' . Module::t('Move'),
                'url' => '#',
                'encode' => false,
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-share-alt']) . ' ' . Module::t('Share'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openShareModal(' . $model->id_storage . ')'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-copy']) . ' ' . Module::t('Make a Copy'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'copyFile(' . $model->id_storage . '); return false;'],
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'deleteFile(' . $model->id_storage . '); return false;'],
            ],
        ],
        'options' => [
            'class' => 'custom-dropdown-menu',
            'id' => 'context-menu-' . $model->id_storage,
        ],
    ]);

    $iconData = $model->getIconUrl();
    $content .= Html::img($iconData['url'], [
        'alt' => $model->title,
        'class' => 'file-icon ' . $iconData['class'],
    ]);

    $content .= Html::endTag('div');
    $content .= Html::endTag('div');
    $content .= Html::endTag('div');
    echo $content;
}

echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'row']);
echo Html::beginTag('div', ['class' => 'col-12 d-flex justify-content-start']);

echo LinkPager::widget([
    'pagination' => $pagination,
    'options' => ['class' => 'pagination pagination-custom'],
    'linkOptions' => ['data-pjax' => true],
    'prevPageLabel' => '<i class="fa fa-chevron-left"></i>',
    'nextPageLabel' => '<i class="fa fa-chevron-right"></i>',
    'maxButtonCount' => 5,
    'firstPageLabel' => '<i class="fa fa-step-backward"></i>',
    'lastPageLabel' => '<i class="fa fa-step-forward"></i>',
    'disableCurrentPageButton' => false,
    'activePageCssClass' => 'active',
]);

echo Html::endTag('div');
echo Html::endTag('div');

echo Html::endTag('div');

$this->registerJs(<<<JS
if (typeof selectFile === 'undefined') {
    window.selectFile = function (element, id_storage) {
        if (window.multiple) {
            if ($(element).is(':checked')) {
                $('.file-card[data-id="' + id_storage + '"]').addClass('active');
            } else {
                $('.file-card[data-id="' + id_storage + '"]').removeClass('active');
            }
        } else {
            $('.file-card.active').removeClass('active');
            $('.file-select-checkbox').not(element).prop('checked', false);
            if ($(element).is(':checked')) {
                $('.file-card[data-id="' + id_storage + '"]').addClass('active');
            }
        }
    };
}

window.handleFileCardClick = function(event, id_storage) {
    if (event.target === this || 
        event.target.classList.contains('file-icon') || 
        event.target.classList.contains('file-title')) { 
        var checkbox = document.querySelector(".file-select-checkbox[value='" + id_storage + "']");
        checkbox.checked = !checkbox.checked;
        if (typeof selectFile === "function") {
            selectFile(checkbox, id_storage);
        }
    }
};
JS
);