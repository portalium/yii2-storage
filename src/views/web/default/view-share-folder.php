<?php

use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Panel;
use portalium\storage\bundles\StorageAsset;
use portalium\theme\widgets\ListView;
use yii\helpers\Url;

/** @var \yii\web\View $this */
/** @var \portalium\storage\models\Storage $model */
/** @var \portalium\storage\models\StorageShare $share */
/** @var \yii\data\ActiveDataProvider $fileDataProvider */
/** @var \yii\data\ActiveDataProvider $directoryDataProvider */
/** @var bool $hasEditAccess */
/** @var bool $hasManageAccess */

StorageAsset::register($this);

$this->title = Module::t('Shared Item');

$breadcrumbs = [];
$current = $model;
$isFirst = true;
while ($current) {
    if ($isFirst) {
        $breadcrumbs[] = Html::encode($current->name);
        $isFirst = false;
    } else {
        $urlFileId = ($current->id_storage == $share->id_directory) ? null : $current->id_storage;
        $breadcrumbs[] = ['label' => Html::encode($current->name), 'url' => ['/storage/default/view-share', 'token' => $share->share_token, 'file_id' => $urlFileId]];
    }
    
    if ($current->id_storage == $share->id_directory || !$current->id_directory) {
        break; 
    }
    $current = \portalium\storage\models\Storage::findOne($current->id_directory);
}
$breadcrumbs[] = ['label' => Module::t('Storage'), 'url' => ['/storage/default/index']];
$breadcrumbs = array_reverse($breadcrumbs);

foreach ($breadcrumbs as $crumb) {
    $this->params['breadcrumbs'][] = $crumb;
}

$viewMode = Yii::$app->request->get('viewMode');
if (!$viewMode) {
    $viewMode = Yii::$app->request->cookies->getValue('viewMode', 'grid');
}

// --- STEP 1: Gathering Buttons (Actions) into an Array ---
$actions = [];

// Everyone: Download and Copy
$actions[] = Html::a(Html::tag('i', '', ['class' => 'fa fa-download']), 
    ['/storage/default/view-share', 'token' => $share->share_token, 'download' => true], 
    ['class' => 'btn btn-info btn-sm', 'title' => Module::t('Download')]);

$actions[] = Html::button(Html::tag('i', '', ['class' => 'fa fa-copy']), 
    ['class' => 'btn btn-success btn-sm', 'title' => Module::t('Make a copy'), 'onclick' => 'copyFile(' . $model->id_storage . '); return false;']);

// Edit Permission: Rename and Update
if ($hasEditAccess) {
    $actions[] = Html::button(Html::tag('i', '', ['class' => 'fa fa-pencil']), 
        ['class' => 'btn btn-warning btn-sm', 'title' => Module::t('Rename'), 'onclick' => 'openRenameModal(' . $model->id_storage . ')']);
    $actions[] = Html::button(Html::tag('i', '', ['class' => 'fa fa-upload']), 
        ['class' => 'btn btn-primary btn-sm', 'title' => Module::t('Update'), 'onclick' => 'openUpdateModal(' . $model->id_storage . ')']);
}

// Manage Permission: Share and Delete
if ($hasManageAccess) {
    $actions[] = Html::button(Html::tag('i', '', ['class' => 'fa fa-share-alt']), 
        ['class' => 'btn btn-info btn-sm', 'title' => Module::t('Share'), 'onclick' => 'openShareModal(' . $model->id_storage . ')']);
    $actions[] = Html::button(Html::tag('i', '', ['class' => 'fa fa-trash']), 
        ['class' => 'btn btn-danger btn-sm', 'title' => Module::t('Remove'), 'onclick' => 'deleteFile(' . $model->id_storage . '); return false;']);
}
?>

<?= Html::beginTag('div', ['class' => 'container-fluid mt-3 file-manager']) ?>

    <?php
    $totalCount = $directoryDataProvider->getTotalCount() + $fileDataProvider->getTotalCount();
    $totalSize = $model->getReadableDirectorySize();
    Panel::begin([
        'title' => Html::tag('i', '', ['class' => 'fa fa-folder me-2 text-warning']) . Html::encode($model->name) . Html::tag('span', ' (' . $totalCount . ' ' . Module::t('items') . ', ' . $totalSize . ')', ['class' => 'text-muted ms-2', 'style' => 'font-size: 14px; font-weight: normal;']),
        'actions' => $actions 
    ]); ?>
    
    <?= Html::beginTag('div', ['class' => 'file-controls mb-3']) ?>
        <?= Html::beginTag('div', ['class' => 'd-flex align-items-center justify-content-end mb-0 flex-shrink-0']) ?>
            <?= Html::beginTag('div', ['class' => 'view-toggle d-flex align-items-center']) ?>
                <?= Html::button(
                    Html::tag('i', '', ['class' => 'fa fa-th me-2']) . Html::tag('span', Module::t('Grid View'), ['class' => 'btn-text']),
                    [
                        'id' => 'btn-grid',
                        'class' => 'btn btn-sm me-2 d-flex align-items-center ' . ($viewMode == 'grid' ? 'btn-selected' : 'btn-unselected'),
                        'type' => 'button',
                        'onclick' => 'setViewMode("grid")',
                    ]
                ) ?>
                <?= Html::button(
                    Html::tag('i', '', ['class' => 'fa fa-list me-2']) . Html::tag('span', Module::t('List View'), ['class' => 'btn-text']),
                    [
                        'id' => 'btn-list',
                        'class' => 'btn btn-sm d-flex align-items-center ' . ($viewMode == 'list' ? 'btn-selected' : 'btn-unselected'),
                        'type' => 'button',
                        'onclick' => 'setViewMode("list")',
                    ]
                ) ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
    <?= Html::endTag('div') ?>

    <?= Html::beginTag('div', ['class' => 'file-list', 'id' => 'file-list-container']) ?>
        
        <?php if ($directoryDataProvider->getTotalCount() > 0): ?>
            <?= Html::beginTag('div', ['class' => 'folders-section ' . $viewMode . '-view mb-4', 'id' => 'folders-section']) ?>
                <?= Html::tag('h3', 
                    Module::t('Your Folders') . Html::tag('i', '', ['class' => 'fa fa-caret-down ms-2 toggle-icon-folders', 'aria-hidden' => 'true']), 
                    ['class' => 'h6 text-muted mb-3 toggle-folders', 'style' => 'cursor: pointer;']) 
                ?>
                <?= Html::beginTag('div', ['class' => 'row ' . ($viewMode == 'grid' ? 'g-3' : ''), 'id' => 'folder-list']) ?>
                    <?php foreach ($directoryDataProvider->models as $dir): ?>
                        <?= Html::beginTag('div', ['class' => 'col-md-2 col-sm-3 col-6 mb-3']) ?>
                            <?= Html::beginTag('div', [
                                'class' => 'folder-item d-flex align-items-center', 
                                'style' => 'cursor: pointer;', 
                                'onclick' => "window.location.href='" . Url::to(['/storage/default/view-share', 'token' => $share->share_token, 'file_id' => $dir->id_storage]) . "'"
                            ]) ?>
                                <?= Html::tag('i', '', ['class' => 'fa fa-folder folder-icon text-warning me-2', 'aria-hidden' => 'true']) ?>
                                <?= Html::tag('span', Html::encode($dir->name), ['class' => 'folder-name']) ?>
                            <?= Html::endTag('div') ?>
                        <?= Html::endTag('div') ?>
                    <?php endforeach; ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?php endif; ?>

        <?= Html::beginTag('div', ['class' => 'files-section ' . $viewMode . '-view', 'id' => 'files-section']) ?>
            <?php if ($fileDataProvider->getTotalCount() > 0): ?>
                <?= Html::tag('h3', 
                    Module::t('Your Files') . Html::tag('i', '', ['class' => 'fa fa-caret-down ms-2 toggle-icon-files', 'aria-hidden' => 'true']), 
                    ['class' => 'h6 text-muted mb-3 toggle-files', 'style' => 'cursor: pointer;']) 
                ?>

                <?= Html::beginTag('div', ['class' => 'file-card file-card-header']) ?>
                    <?= Html::beginTag('div', ['class' => 'file-item']) ?>
                        <?= Html::beginTag('div', ['class' => 'file-header']) ?>
                            <?= Html::beginTag('div', ['class' => 'file-info']) ?>
                                <?= Html::tag('i', '', ['class' => 'fa fa-bars file-icon', 'style' => 'color:transparent;']) ?>
                                <?= Html::tag('span', Module::t('File Name'), ['class' => 'file-title']) ?>
                                <?= Html::tag('span', Module::t('Owner'), ['class' => 'file-owner']) ?>
                                <?= Html::tag('span', Module::t('Date Update'), ['class' => 'file-date']) ?>
                                <?= Html::tag('span', Module::t('Size'), ['class' => 'file-size']) ?>
                            <?= Html::endTag('div') ?>
                            <?= Html::button(Html::tag('i', '', ['class' => 'fa fa-ellipsis-v']), ['class' => 'file-more-options', 'style' => 'color:transparent;']) ?>
                        <?= Html::endTag('div') ?>
                    <?= Html::endTag('div') ?>
                <?= Html::endTag('div') ?>
            <?php endif; ?>

            <?php
            $listViewOptions = ['id' => 'file-list', 'class' => ''];
            if ($viewMode === 'grid') {
                $listViewOptions['class'] = 'file-grid mb-3';
            }
            
            echo ListView::widget([
                'dataProvider' => $fileDataProvider,
                'layout' => Html::beginTag('div', $listViewOptions) . "{items}" . Html::endTag('div') .
                    '<div class="panel-footer d-flex justify-content-between mt-3">' .
                    '<div class="d-flex align-items-start">{summary}</div>' .
                    '<div class="d-flex" style="gap: 10px;">{pagesizer}{pager}</div>' .
                    '</div>',
                'customLayout' => true,
                'pageSizer' => ['availableSizes' => [12 => 12, 24 => 24, 48 => 48]],
                'emptyText' => Module::t('No files found.'),
                'itemView' => function ($itemModel, $key, $index, $widget) use ($share, $hasEditAccess, $hasManageAccess) {
                    $content = Html::beginTag('div', [
                        'class' => 'file-card',
                        'data-id' => $itemModel->id_storage,
                    ]);

                    $content .= Html::beginTag('div', [
                        'class' => 'file-item',
                        'data-url' => Url::to(['/storage/default/view-share', 'token' => $share->share_token, 'file_id' => $itemModel->id_storage]),
                        'data-attributes' => json_encode([
                            'id_storage' => $itemModel->id_storage,
                            'share_token' => $share->id_share,
                            'name' => $itemModel->name,
                            'title' => $itemModel->title,
                            'mime_type' => $itemModel->mime_type,
                            'icon_class_php' => $itemModel->getIconClass(),
                        ]),
                        // This triggers the code within StorageAsset so that a preview opens when the file is double-clicked.
                        'ondblclick' => "if (typeof handleFileCardClick === 'function') handleFileCardClick.call(this, event, " . $itemModel->id_storage . ")",
                    ]);

                    $content .= Html::beginTag('div', ['class' => 'file-header']);
                    $content .= Html::beginTag('div', ['class' => 'file-info']);
                    $content .= Html::tag('i','',['class'=> $itemModel->getIconClass() . ' file-icon text-muted']);
                    $content .= Html::tag('span', Html::encode($itemModel->title ?: 'No Title'), ['class' => 'file-title normal']);
                    $content .= Html::tag('span', Html::encode($itemModel->user->username ?? 'Unknown'), ['class' => 'file-owner text-muted']);
                    $content .= Html::tag('span', Yii::$app->formatter->asDatetime($itemModel->date_update, 'php:d.m.Y H:i'), ['class' => 'file-date text-muted']);
                    
                    if ($itemModel->type === \portalium\storage\models\Storage::TYPE_FILE) {
                        $content .= Html::tag('span', $itemModel->getReadableFileSize(), ['class' => 'file-size text-muted']);
                    } else {
                        $content .= Html::tag('span', '--', ['class' => 'file-size text-muted']);
                    }

                    $content .= Html::endTag('div'); // .file-info

                    // More Options Dropdown
                    $dropdownItems = [];
                    $dropdownItems[] = [
                        'label' => Html::tag('i', '', ['class' => 'fa fa-download me-2']) . Module::t('Download'),
                        'url' => ['/storage/default/view-share', 'token' => $share->share_token, 'file_id' => $itemModel->id_storage, 'download' => true],
                        'linkOptions' => ['class' => 'dropdown-item', 'data-pjax' => 0]
                    ];
                    $dropdownItems[] = [
                        'label' => Html::tag('i', '', ['class' => 'fa fa-copy me-2']) . Module::t('Make a copy'),
                        'url' => '#',
                        'linkOptions' => [
                            'class' => 'dropdown-item',
                            'onclick' => 'copyFile(' . $itemModel->id_storage . '); return false;'
                        ]
                    ];

                    if ($hasEditAccess) {
                        $dropdownItems[] = [
                            'label' => Html::tag('i', '', ['class' => 'fa fa-pencil me-2']) . Module::t('Rename'),
                            'url' => '#',
                            'linkOptions' => [
                                'class' => 'dropdown-item',
                                'onclick' => 'openRenameModal(' . $itemModel->id_storage . '); return false;'
                            ]
                        ];
                        $dropdownItems[] = [
                            'label' => Html::tag('i', '', ['class' => 'fa fa-upload me-2']) . Module::t('Update'),
                            'url' => '#',
                            'linkOptions' => [
                                'class' => 'dropdown-item',
                                'onclick' => 'openUpdateModal(' . $itemModel->id_storage . '); return false;'
                            ]
                        ];
                    }

                    if ($hasManageAccess) {
                        $dropdownItems[] = [
                            'label' => Html::tag('i', '', ['class' => 'fa fa-share-alt me-2']) . Module::t('Share'),
                            'url' => '#',
                            'linkOptions' => [
                                'class' => 'dropdown-item',
                                'onclick' => 'openShareModal(' . $itemModel->id_storage . '); return false;'
                            ]
                        ];
                        $dropdownItems[] = [
                            'label' => Html::tag('i', '', ['class' => 'fa fa-trash me-2']) . Module::t('Delete'),
                            'url' => '#',
                            'linkOptions' => [
                                'class' => 'dropdown-item text-danger',
                                'onclick' => 'deleteFile(' . $itemModel->id_storage . '); return false;'
                            ]
                        ];
                    }

                    $dropdownHtml = \portalium\theme\widgets\Dropdown::widget([
                        'items' => $dropdownItems,
                        'options' => ['class' => 'dropdown-menu dropdown-menu-end'],
                        'encodeLabels' => false,
                    ]);

                    $content .= Html::beginTag('div', ['class' => 'dropdown']);
                    $content .= Html::button(
                        Html::tag('i', '', ['class' => 'fa fa-ellipsis-v']),
                        [
                            'class' => 'file-more-options btn-link',
                            'data-bs-toggle' => 'dropdown',
                            'aria-expanded' => 'false',
                        ]
                    );
                    $content .= $dropdownHtml;
                    $content .= Html::endTag('div'); // .dropdown

                    $content .= Html::endTag('div'); // .file-header

                    // file preview
                    $content .= Html::beginTag('div', ['class' => 'file-preview']);
                    $iconData = $itemModel->getIconUrl();
                    
                    // Fetch real thumbnails for images using our new action
                    if (in_array(intval($itemModel->mime_type), [0, 1, 17, 25])) {
                        $thumbUrl = Url::to(['/storage/default/view-share', 'token' => $share->share_token, 'file_id' => $itemModel->id_storage, 'type' => 'thumb']);
                        $content .= Html::img($thumbUrl, [
                            'alt' => $itemModel->title,
                            'class' => 'file-icon img-fluid',
                            'style' => 'width: 100%; height: 100%; object-fit: cover;',
                        ]);
                    } else {
                        $content .= Html::img($iconData['url'], [
                            'alt' => $itemModel->title,
                            'class' => 'file-icon ' . $iconData['class'],
                            'style' => 'width: 100%; height: 100%; object-fit: cover;',
                        ]);
                    }
                    $content .= Html::endTag('div'); // .file-preview

                    $content .= Html::endTag('div'); // .file-item
                    $content .= Html::endTag('div'); // .file-card

                    return $content;
                }
            ]);
            ?>
        </div>
    </div>
    <?php Panel::end(); ?>
    <?= $this->render('_filePreviewModal'); ?>
</div>
<?php
use portalium\widgets\Pjax;

Pjax::begin(['id' => 'rename-file-pjax', 'history' => false, 'timeout' => false, 'enablePushState' => false]); Pjax::end();
Pjax::begin(['id' => 'update-file-pjax', 'history' => false, 'timeout' => false, 'enablePushState' => false]); Pjax::end();
Pjax::begin(['id' => 'share-file-pjax', 'history' => false, 'timeout' => false, 'enablePushState' => false]); Pjax::end();
?>

<?= Html::beginTag('div', ['class' => 'modal fade', 'id' => 'shareModal', 'tabindex' => '-1', 'aria-hidden' => 'true']) ?>
    <?= Html::beginTag('div', ['class' => 'modal-dialog modal-lg']) ?>
        <?= Html::tag('div', '', ['class' => 'modal-content']) ?>
    <?= Html::endTag('div') ?>
<?= Html::endTag('div') ?>

<?= Html::hiddenInput('current-share-id', $share->id_share, ['id' => 'current-share-id']) ?>
<?= Html::hiddenInput('current-storage-id', $model->id_storage, ['id' => 'current-storage-id']) ?>

<?php
Pjax::begin([
    'id' => 'list-item-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);
Pjax::end();
?>

<?php $this->registerJs("
    $.ajaxSetup({
        data: {
            '" . Yii::$app->request->csrfParam . "': '" . Yii::$app->request->getCsrfToken() . "'
        }
    });
"); ?>

<?= Html::tag('div', '', ['id' => 'toast-container', 'class' => 'toast-container position-fixed top-0 end-0 p-3']) ?>
