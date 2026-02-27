<?php

use portalium\storage\models\StorageDirectory;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Dropdown;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use portalium\storage\bundles\IconAsset;
use portalium\user\models\User;
use portalium\theme\widgets\ListView;   


/** @var \yii\data\ActiveDataProvider $directoryDataProvider */
/** @var \yii\data\ActiveDataProvider $fileDataProvider */
/** @var bool $isPicker */
/** @var string $actionId */
$actionId = $actionId ?? 'index';

$bundle = \portalium\storage\bundles\IconAsset::register($this);

$id_directory = Yii::$app->request->get('id_directory');
$parentDirectory = null;


$fileExtensions = Yii::$app->request->get('fileExtensions', []);


if (is_string($fileExtensions) && !empty($fileExtensions)) {
    $fileExtensions = explode(',', $fileExtensions);
}
if (!is_array($fileExtensions)) {
    $fileExtensions = [];
}


$fileExtensionsParam = !empty($fileExtensions) ? implode(',', $fileExtensions) : '';

if ($id_directory !== null) {
    $parentDirectory = StorageDirectory::findOne($id_directory);
}

echo Html::beginTag('div', ['class' => 'container-fluid mt-3']);


if ($id_directory !== null) {
    $parentId = $parentDirectory && $parentDirectory->id_parent ? $parentDirectory->id_parent : null;
    $backUrlParams = [$actionId, 'isPicker' => $isPicker];
    if ($parentId) {
        $backUrlParams['id_directory'] = $parentId;
    }
    if (!empty($fileExtensionsParam)) {
        $backUrlParams['fileExtensions'] = $fileExtensionsParam;
    }

    echo Html::a(
        Html::tag('i', '', ['class' => 'fa fa-chevron-left']) . ' ',
        $backUrlParams,
        ['class' => 'btn btn-lg', 'data-pjax' => true, 'onclick' => 'currentDirectoryId = ' . ($parentId ? $parentId : 'null') . ';', 'data-pjax-push-state' => 'false', 'data-pjax-replace-state' => 'false']
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


    $homeUrlParams = ['index', 'isPicker' => $isPicker];
    if (!empty($fileExtensionsParam)) {
        $homeUrlParams['fileExtensions'] = $fileExtensionsParam;
    }

    echo Html::tag(
        'li',
        Html::a(Module::t('Home'), $homeUrlParams, ['data-pjax' => true, 'onclick' => 'currentDirectoryId = null;', 'data-pjax-push-state' => 'false', 'data-pjax-replace-state' => 'false']),
        ['class' => 'breadcrumb-item']
    );

    foreach ($pathItems as $i => $item) {
        if ($i === count($pathItems) - 1) {
            echo Html::tag('li', Html::encode($item['name']), ['class' => 'breadcrumb-item active']);
        } else {

            $breadcrumbUrlParams = ['index', 'id_directory' => $item['id'], 'isPicker' => $isPicker];
            if (!empty($fileExtensionsParam)) {
                $breadcrumbUrlParams['fileExtensions'] = $fileExtensionsParam;
            }

            echo Html::tag(
                'li',
                Html::a(Html::encode($item['name']), $breadcrumbUrlParams, ['data-pjax' => true, 'onclick' => 'currentDirectoryId = ' . $item['id'] . ';', 'data-pjax-push-state' => 'false', 'data-pjax-replace-state' => 'false']),
                ['class' => 'breadcrumb-item']
            );
        }
    }

    echo Html::endTag('ol');
    echo Html::endTag('nav');
}


echo Html::beginTag('div', ['class' => 'folders-section mb-4', 'id' => 'folders-section']);

$directories = $directoryDataProvider->models;

if (!empty($directories)) {
echo Html::tag('h3', 
    Module::t('Your Folders') . ' ' . Html::tag('i', '', [
        'class' => 'fa fa-caret-down ms-2 toggle-icon-folders',
        'aria-hidden' => 'true'
    ]), 
    ['class' => 'h6 text-muted mb-3 toggle-folders', 'style' => 'cursor: pointer;']
);
echo Html::beginTag('div', ['class' => 'row g-3', 'id' => 'folder-list']); 
}

foreach ($directories as $model) {
    /** @var \portalium\storage\models\StorageDirectory $model */
    $folderId = $model->id_directory;
    $folderName = Html::encode($model->name);

    $content = Html::beginTag('div', [
        'class' => ($isPicker ? 'col-md-3 col-sm-6 col-12 mb-3' : 'col-md-2 col-sm-3 col-6 mb-3'),
        'id' => 'folder-' . $folderId,
    ]);

    $content .= Html::beginTag('div', [
        'class' => 'folder-item d-flex align-items-center',
        'data-id' => $folderId,
        'ondblclick' => "if (!(event.target.closest('.more-options'))) { openFolder($folderId, event, '" . $fileExtensionsParam . "'); }",
    ]);

    $content .= Html::tag('i', '', [
    'class' => 'fa fa-folder folder-icon',
    'aria-hidden' => 'true'
    ]);

    $content .= Html::tag('span', $folderName, ['class' => 'folder-name']);

    $content .= Html::button(
    Html::tag('i', '', ['class' => 'fa fa-ellipsis-v']),
    [
        'class' => 'more-options',
        'onclick' => "toggleFolderMenu(event, $folderId)",
        'data-title' => Module::t('More Options'),
    ]
    );

    // Check user's permissions for this folder
    $isOwner = ($model->id_user == Yii::$app->user->id);
    $hasGlobalEditPerm = Yii::$app->user->can('storageWebDefaultRenameFolder') || Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFolder', ['model' => $model]);
    $hasGlobalDeletePerm = Yii::$app->user->can('storageWebDefaultDeleteFolder') || Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFolder', ['model' => $model]);
    
    // Check share permissions
    $hasEditPermission = $isOwner || $hasGlobalEditPerm || \portalium\storage\models\StorageShare::hasAccess(
        Yii::$app->user->id, 
        null, 
        $model, 
        \portalium\storage\models\StorageShare::PERMISSION_EDIT
    );
    
    $hasManagePermission = $isOwner || $hasGlobalDeletePerm || \portalium\storage\models\StorageShare::hasAccess(
        Yii::$app->user->id, 
        null, 
        $model, 
        \portalium\storage\models\StorageShare::PERMISSION_MANAGE
    );

    $dropdownItems = [];
    
    // Rename - requires Edit permission
    if ($hasEditPermission) {
        $dropdownItems[] = [
            'label' => Html::tag('i', '', ['class' => 'fa fa-pencil']) . ' ' . Module::t('Rename'),
            'url' => '#',
            'encode' => false,
            'linkOptions' => ['onclick' => 'openRenameFolderModal(' . $folderId . ')'],
        ];
    }
    
    /* Move - requires Edit permission
    if ($hasEditPermission) {
        $dropdownItems[] = [
            'label' => Html::tag('i', '', ['class' => 'fa fa-arrows-alt']) . ' ' . Module::t('Move'),
            'url' => '#',
            'encode' => false,
            'linkOptions' => ['onclick' => 'openMoveFolderModal(' . $folderId . ')'],
        ];
    }
    */
    
    // Share - requires Manage permission
    if ($hasManagePermission) {
        $dropdownItems[] = [
            'label' => Html::tag('i', '', ['class' => 'fa fa-share-alt']) . ' ' . Module::t('Share'),
            'url' => '#',
            'encode' => false,
            'linkOptions' => ['onclick' => 'openShareFolderModal(' . $folderId . ')'],
        ];
    }
    
    // Delete - requires Manage permission
    if ($hasManagePermission) {
        $dropdownItems[] = [
            'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
            'url' => '#',
            'encode' => false,
            'linkOptions' => [
                'onclick' => 'deleteFolder(' . $folderId . '); return false;',
                'data-id' => $folderId,
            ],
        ];
    }

    $content .= Dropdown::widget([
        'items' => $dropdownItems,
        'options' => [
            'class' => 'folder-dropdown-menu',
            'id' => 'context-folder-menu-' . $folderId,
        ],
    ]);

    $content .= Html::endTag('div'); 
    $content .= Html::endTag('div'); 
    echo $content; 
}
if (!empty($directories)){
    echo Html::endTag('div'); // row g-3
}
echo Html::endTag('div'); // folders-section

echo Html::beginTag('div', [
    'id' => 'bulk-action-toolbar',
    'class' => 'bulk-action-toolbar d-none',
    'style' => 'background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px; z-index: 999; margin-bottom: 20px;'
]);

echo Html::beginTag('div', ['class' => 'd-flex align-items-center justify-content-between']);

echo Html::tag('span', '', [
    'id' => 'bulk-selection-count',
    'class' => 'text-muted',
]);

echo Html::beginTag('div', ['class' => 'gap-2 d-flex']);

echo Html::button(
    Html::tag('i', '', ['class' => 'fa fa-download me-2']) . Module::t('Download Selected'),
    [
        'class' => 'btn btn-success btn-sm',
        'id' => 'bulk-download-btn',
        'onclick' => 'bulkDownloadFiles()',
    ]
);

echo Html::button(
    Html::tag('i', '', ['class' => 'fa fa-trash me-2']) . Module::t('Delete Selected'),
    [
        'class' => 'btn btn-danger btn-sm',
        'id' => 'bulk-delete-btn',
        'onclick' => 'bulkDeleteFiles()',
    ]
);

echo Html::button(
    Module::t('Cancel'),
    [
        'class' => 'btn btn-primary btn-sm',
        'id' => 'bulk-cancel-btn',
        'onclick' => 'clearBulkSelection()',
    ]
);

echo Html::endTag('div');
echo Html::endTag('div');
echo Html::endTag('div'); // bulk-action-toolbar

echo Html::beginTag('div', ['class' => 'files-section', 'id' => 'files-section']);

if ($fileDataProvider->getTotalCount() > 0) {
    echo Html::tag('h3', 
        Module::t('Your Files') . ' ' . Html::tag('i', '', [
            'class' => 'fa fa-caret-down ms-2 toggle-icon-files',
            'aria-hidden' => 'true'
        ]), 
        ['class' => 'h6 text-muted mb-3 toggle-files', 'style' => 'cursor: pointer;']
    );

    $header  = Html::beginTag('div', ['class' => 'file-card file-card-header']);
    $header .= Html::beginTag('div', ['class' => 'file-item']);
    $header .= Html::beginTag('div', ['class' => 'file-header']);
    $header .= Html::beginTag('div', ['class' => 'file-info']);
    $header .= Html::tag('i','',['class'=>'fa fa-bars file-icon','style'=>'color:transparent;']);

    $header .= Html::tag('span', Module::t('File Name'), ['class' => 'file-title']);
    $header .= Html::tag('span', Module::t('Owner'), ['class' => 'file-owner']);
    $header .= Html::tag('span', Module::t('Date Update'), ['class' => 'file-date']);
    $header .= Html::tag('span', Module::t('Access'), ['class' => 'file-access']);

    $header .= Html::endTag('div'); // .file-info
    $header .= Html::button(
            Html::tag('i', '', [
                'class' => 'fa fa-ellipsis-v',
                'style'=>'color:transparent;'
            ]),
            [
                'class' => 'file-more-options',
            ]
        );
    $header .= Html::endTag('div'); // .file-header
    $header .= Html::endTag('div'); // .file-item
    $header .= Html::endTag('div'); // .file-card

    echo $header;
}

$viewMode = Yii::$app->request->get('viewMode');
if (!$viewMode) {
    $viewMode = Yii::$app->request->cookies->getValue('viewMode', 'grid');
}

$listViewOptions = ['id' => 'file-list'];
if ($viewMode === 'grid') {
    $listViewOptions['class'] = $isPicker ? 'file-grid picker mb-3' : 'file-grid mb-3';
}

$sortField = Yii::$app->request->get('sortField', null);
$sortDirection = Yii::$app->request->get('sortDirection', 'desc');

// Get selected file id in file picker
$selectedFileId = Yii::$app->request->get('selectedFileId', null);

if ($fileDataProvider && $fileDataProvider->query) {
    if ($isPicker && $selectedFileId) {
        if ($sortField === 'name') {
            $fileDataProvider->query->orderBy([
                new \yii\db\Expression("CASE WHEN id_storage = :selectedId THEN 0 ELSE 1 END", [':selectedId' => $selectedFileId]),
                'title' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        } elseif ($sortField === 'last_accessed') {
            $fileDataProvider->query->orderBy([
                new \yii\db\Expression("CASE WHEN id_storage = :selectedId THEN 0 ELSE 1 END", [':selectedId' => $selectedFileId]),
                'date_last_access' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        } elseif ($sortField === 'most_accessed') {
            $fileDataProvider->query->orderBy([
                new \yii\db\Expression("CASE WHEN id_storage = :selectedId THEN 0 ELSE 1 END", [':selectedId' => $selectedFileId]),
                'access_count' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        } else {
            $fileDataProvider->query->orderBy([
                new \yii\db\Expression("CASE WHEN id_storage = :selectedId THEN 0 ELSE 1 END", [':selectedId' => $selectedFileId]),
                'date_create' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
                'id_storage' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        }
    } else {
        if ($sortField === 'name') {
            $fileDataProvider->query->orderBy([
                'title' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        } elseif ($sortField === 'last_accessed') {
            $fileDataProvider->query->orderBy([
                'date_last_access' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        } elseif ($sortField === 'most_accessed') {
            $fileDataProvider->query->orderBy([
                'access_count' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        } elseif ($sortField === null || $sortField === 'default') {
            $fileDataProvider->query->orderBy([
                'date_create' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
                'id_storage' => ($sortDirection === 'desc') ? SORT_DESC : SORT_ASC,
            ]);
        }
    }
}

echo ListView::widget([
    'dataProvider' => $fileDataProvider,
    'layout' =>
         Html::beginTag('div', $listViewOptions) . "{items}" . Html::endTag('div') .
        '<div class="panel-footer d-flex justify-content-between mt-3">'
        . '<div class="d-flex align-items-start">{summary}</div>'
        . '<div id="file-page-sizer" class="d-flex" style="gap: 10px;">{pagesizer}{pager}</div>'
        . '</div>',
    'customLayout' => true,
    'emptyText' => '',
    'itemView' => function ($model, $key, $index, $widget) use ($isPicker) {

        $fileCardClasses = 'file-card';

        $content = Html::beginTag('div', [
            'class' => $fileCardClasses,
            'data-id' => $model->id_storage,
            'data-attributes' => json_encode([
                'id_storage' => $model->id_storage,
                'name' => $model->name,
                'title' => $model->title,
                'mime_type' => $model->mime_type,
                'icon_class_php' => $model->getIconClass(),
            ])
        ]);

        $content .= Html::beginTag('div', [
            'class' => 'file-item',
            'data-url' => Url::to(['/storage/default/get-file', 'id' => $model->id_storage]),
            'data-attributes' => json_encode([
                'id_storage' => $model->id_storage,
                'name' => $model->name,
                'title' => $model->title,
                'mime_type' => $model->mime_type,
                'icon_class_php' => $model->getIconClass(),
            ]),
            'onclick' => $isPicker ? 'handleFileCardClick.call(this, event, ' . $model->id_storage . ')' : null,
        ]);

        $content .= Html::beginTag('div', ['class' => 'file-header']);
        $content .= Html::beginTag('div', ['class' => 'file-info']);

        if ($isPicker) {
            $content .= Html::checkbox('selection', false, [
                'class' => 'file-select-checkbox',
                'value' => $model->id_storage,
                'onclick' => 'selectFile(this, ' . $model->id_storage . ')',
            ]);
        }

        $content .= Html::tag('i','',['class'=> $model->getIconClass() . ' file-icon']);
        $title = $model->title ?: 'Başlık yok';
        $titleAttrs = ['class' => 'file-title ' . ($isPicker ? 'picker' : 'normal'), 'data-title' => $title];
        $content .= Html::tag('span', Html::encode($title), $titleAttrs);

        $content .= Html::tag(
            'span',
            Html::encode(User::find()->select('username')->where(['id_user' => $model->id_user])->scalar() ?? 'Bilinmiyor'),
            ['class' => 'file-owner text-muted']
        );

        $content .= Html::tag('span', Yii::$app->formatter->asDatetime($model->date_update, 'php:d.m.Y H:i'), [
            'class' => 'file-date text-muted',
        ]);

        $content .= Html::tag('span', $model->accessText, [
            'class' => 'file-access text-muted',
        ]);

        $content .= Html::endTag('div'); // .file-info

        $content .= Html::button(
            Html::tag('i', '', [
                'class' => 'fa fa-ellipsis-v',
                'id' => 'menu-trigger-' . $model->id_storage,
                'data-title' => $title,
            ]),
            [
                'class' => 'file-more-options',
                'onclick' => 'toggleContextMenu(event, ' . $model->id_storage . ')',
                'data-title' => Module::t('More Options'),
            ]
        );

        $content .= Html::endTag('div'); // .file-header

        // Check user's permissions for this file
        $isOwner = ($model->id_user == Yii::$app->user->id);
        $hasGlobalEditPerm = Yii::$app->user->can('storageWebDefaultRenameFile') || Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFile', ['model' => $model]);
        $hasGlobalDeletePerm = Yii::$app->user->can('storageWebDefaultDeleteFile') || Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFile', ['model' => $model]);
        
        // Check share permissions
        $hasViewPermission = $isOwner || Yii::$app->user->can('storageWebDefaultIndex') || \portalium\storage\models\StorageShare::hasAccess(
            Yii::$app->user->id, 
            $model, 
            null, 
            \portalium\storage\models\StorageShare::PERMISSION_VIEW
        );
        
        $hasEditPermission = $isOwner || $hasGlobalEditPerm || \portalium\storage\models\StorageShare::hasAccess(
            Yii::$app->user->id, 
            $model, 
            null, 
            \portalium\storage\models\StorageShare::PERMISSION_EDIT
        );
        
        $hasManagePermission = $isOwner || $hasGlobalDeletePerm || \portalium\storage\models\StorageShare::hasAccess(
            Yii::$app->user->id, 
            $model, 
            null, 
            \portalium\storage\models\StorageShare::PERMISSION_MANAGE
        );

        // Build dropdown items based on permissions
        $fileDropdownItems = [];
        
        // Download - requires View permission
        if ($hasViewPermission) {
            $fileDropdownItems[] = [
                'label' => Html::tag('i', '', ['class' => 'fa fa-download']) . ' ' . Module::t('Download'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'downloadFile(' . $model->id_storage . '); return false;'],
            ];
        }
        
        // Rename - requires Edit permission
        if ($hasEditPermission) {
            $fileDropdownItems[] = [
                'label' => Html::tag('i', '', ['class' => 'fa fa-pencil']) . ' ' . Module::t('Rename'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openRenameModal(' . $model->id_storage . ')'],
            ];
        }
        
        // Update - requires Edit permission
        if ($hasEditPermission) {
            $fileDropdownItems[] = [
                'label' => Html::tag('i', '', ['class' => 'fa fa-refresh']) . ' ' . Module::t('Update'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openUpdateModal(' . $model->id_storage . ')'],
            ];
        }
        
        /* Move - requires Edit permission
        if ($hasEditPermission) {
            $fileDropdownItems[] = [
                'label' => Html::tag('i', '', ['class' => 'fa fa-arrows-alt']) . ' ' . Module::t('Move'),
                'url' => '#',
                'encode' => false,
            ];
        }
        */
        
        // Share - requires Manage permission
        if ($hasManagePermission) {
            $fileDropdownItems[] = [
                'label' => Html::tag('i', '', ['class' => 'fa fa-share-alt']) . ' ' . Module::t('Share'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'openShareModal(' . $model->id_storage . ')'],
            ];
        }
        
        // Make a Copy - requires View permission
        if ($hasViewPermission) {
            $fileDropdownItems[] = [
                'label' => Html::tag('i', '', ['class' => 'fa fa-copy']) . ' ' . Module::t('Make a Copy'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'copyFile(' . $model->id_storage . '); return false;'],
            ];
        }
        
        // Delete - requires Manage permission
        if ($hasManagePermission) {
            $fileDropdownItems[] = [
                'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
                'url' => '#',
                'encode' => false,
                'linkOptions' => ['onclick' => 'deleteFile(' . $model->id_storage . '); return false;'],
            ];
        }
        
        // Dropdown menu
        $content .= Dropdown::widget([
            'items' => $fileDropdownItems,
            'options' => [
                'class' => 'custom-dropdown-menu',
                'id' => 'context-menu-' . $model->id_storage,
            ],
        ]);

        // file preview
        $content .= Html::beginTag('div', ['class' => 'file-preview']);
        $iconData = $model->getIconUrl();
        $content .= Html::img($iconData['url'], [
            'alt' => $model->title,
            'class' => 'file-icon ' . $iconData['class'],
            'style' => 'width: 100%; height: 100%;',
        ]);
        $content .= Html::endTag('div'); // .file-preview

        $content .= Html::endTag('div'); // .file-item
        $content .= Html::endTag('div'); // .file-card

        return $content;
    },
]);

echo Html::endTag('div'); // .files-section
echo Html::endTag('div'); // end of container-fluid

$this->registerJsVar('isPicker', $isPicker ? 1 : 0);
$this->registerJsVar('currentFileExtensions', $fileExtensionsParam);
$this->registerJsVar('actionId', $actionId);

$this->registerJsVar('translations', [
    'fileSelected' => Module::t('file selected'),
    'filesSelected' => Module::t('files selected'),
    'selectToDelete' => Module::t('Please select files to delete'),
    'selectToDownload' => Module::t('Please select files to download'),
    'confirmDelete' => Module::t('Are you sure you want to delete {count} files?'),
    'downloading' => Module::t('Downloading...'),
    'download' => Module::t('Download'),
]);

$this->registerJs(<<<JS
if (window.isPicker) {
    if (typeof window.setPickerContext === 'function') {
        window.setPickerContext(true);
    }
    \$('body').addClass('picker-context');
}

if (!window.selectedFiles) {
    window.selectedFiles = new Set();
}

window.updateBulkToolbar = function() {
    const count = window.selectedFiles.size;
    const toolbar = \$('#bulk-action-toolbar');
    const countSpan = \$('#bulk-selection-count');
    
    console.log('updateBulkToolbar called, count:', count);
    
    if (count > 0) {
        toolbar.removeClass('d-none');
        const text = count === 1 ? window.translations.fileSelected : window.translations.filesSelected;
        countSpan.text(count + ' ' + text);
    } else {
        toolbar.addClass('d-none');
        countSpan.text('');
    }
};

window.saveBulkSelection = function() {
    const selectedArray = Array.from(window.selectedFiles);
    localStorage.setItem('bulkSelectedFiles', JSON.stringify(selectedArray));
    console.log('Bulk selection saved:', selectedArray);
};

window.restoreBulkSelection = function() {
    try {
        const saved = localStorage.getItem('bulkSelectedFiles');
        if (saved) {
            const selectedArray = JSON.parse(saved);
            console.log('Restoring bulk selection:', selectedArray);
            
            window.selectedFiles.clear();
            selectedArray.forEach(id => window.selectedFiles.add(id));
            
            selectedArray.forEach(id => {
                const el = \$('.file-card[data-id="' + id + '"]');
                if (el.length > 0) {
                    el.addClass('bulk-selected');
                    console.log('Highlighted visible file:', id);
                }
            });
            
            if (typeof window.updateBulkToolbar === 'function') {
                window.updateBulkToolbar();
            }
        }
    } catch (e) {
        console.error('Error restoring bulk selection:', e);
    }
};

window.toggleBulkSelection = function(id_storage, event) {
    console.log('toggleBulkSelection called with:', {
        id_storage: id_storage,
        event: event,
        hasCtrlKey: event && event.ctrlKey,
    });
    
    if (event && event.ctrlKey) {
        event.preventDefault();
        event.stopPropagation();
        
        console.log('Before toggle - selectedFiles:', Array.from(window.selectedFiles));
        
        const fileCard = \$(".file-card[data-id='" + id_storage + "']");
        
        if (window.selectedFiles.has(id_storage)) {
            window.selectedFiles.delete(id_storage);
            console.log('Removed from selection:', id_storage);
            fileCard.removeClass('bulk-selected');
        } else {
            window.selectedFiles.add(id_storage);
            console.log('Added to selection:', id_storage);
            fileCard.addClass('bulk-selected');
        }
        
        console.log('After toggle - selectedFiles:', Array.from(window.selectedFiles));
        
        saveBulkSelection();
        updateBulkToolbar();
        
        return false;
    }
    return true;
};

window.clearBulkSelection = function() {
    window.clearBulkSelectionAndStorage();
};

window.clearBulkSelectionAndStorage = function() {
    console.log('Clearing all bulk selections');
    
    \$('.file-card.bulk-selected').removeClass('bulk-selected');
    
    window.selectedFiles.clear();
    
    localStorage.removeItem('bulkSelectedFiles');
    
    
    if (typeof window.updateBulkToolbar === 'function') {
        window.updateBulkToolbar();
    }
    
    console.log('Bulk selection cleared');
};

window.bulkDownloadFiles = function() {
    if (window.selectedFiles.size === 0) {
        alert(window.translations.selectToDownload || 'Please select files to download');
        return;
    }
    
    const btn = \$('#bulk-download-btn');
    const originalText = btn.html();
    btn.html('<i class="fa fa-spinner fa-spin me-2"></i>' + (window.translations.downloading || 'Downloading...'));
    btn.prop('disabled', true);
    
    const selectedArray = Array.from(window.selectedFiles);
    let completed = 0;
    
    selectedArray.forEach(id => {
        if (typeof window.downloadFile === 'function') {
            window.downloadFile(id);
        }
        completed++;
        if (completed === selectedArray.length) {
            setTimeout(() => {
                btn.html(originalText);
                btn.prop('disabled', false);
            }, 1000);
        }
    });
};

window.bulkDeleteFiles = function() {
    if (window.selectedFiles.size === 0) {
        alert(window.translations.selectToDelete || 'Please select files to delete');
        return;
    }
    
    const count = window.selectedFiles.size;
    const confirmMsg = (window.translations.confirmDelete || 'Are you sure you want to delete {count} files?').replace('{count}', count);
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    const selectedArray = Array.from(window.selectedFiles);
    let completed = 0;
    
    selectedArray.forEach(id => {
        \$.ajax({
            url: '/storage/default/delete-file',
            type: 'POST',
            data: {
                id: id,
                id_directory: window.currentDirectoryId || null,
                isPicker: window.currentIsPicker ? '1' : '0',
            },
            headers: {
                'X-CSRF-Token': \$('meta[name="csrf-token"]').attr('content'),
            },
            complete: function() {
                completed++;
                window.selectedFiles.delete(id);
                saveBulkSelection();
                
                if (completed === selectedArray.length) {
                    if (typeof window.refreshCurrentView === 'function') {
                        window.refreshCurrentView();
                    }
                    updateBulkToolbar();
                }
            }
        });
    });
};

\$(document).on('pjax:end', function() {
    console.log('PJAX END - Restoring selections in itemlist.php');
    console.log('Current selectedFiles before restore:', Array.from(window.selectedFiles));
    
    const savedFolderState = localStorage.getItem('folderListOpen');
    if (savedFolderState === 'true') {
        \$('#folder-list').show();
        \$('.toggle-icon-folders').removeClass('fa-caret-right').addClass('fa-caret-down');
    } else if (savedFolderState === 'false') {
        \$('#folder-list').hide();
        \$('.toggle-icon-folders').removeClass('fa-caret-down').addClass('fa-caret-right');
    }
    
    const savedFileState = localStorage.getItem('fileListOpen');
    if (savedFileState === 'true') {
        \$('#file-list').show();
        \$('.toggle-icon-files').removeClass('fa-caret-right').addClass('fa-caret-down');
    } else if (savedFileState === 'false') {
        \$('#file-list').hide();
        \$('.toggle-icon-files').removeClass('fa-caret-down').addClass('fa-caret-right');
    }
    
    if (typeof window.restoreBulkSelection === 'function') {
        console.log('Calling restoreBulkSelection...');
        window.restoreBulkSelection();
    }
    
    console.log('Current selectedFiles after restore:', Array.from(window.selectedFiles));
});

\$(document).ready(function() {
    console.log('Document ready in itemlist.php - restoring selections');
    if (typeof window.restoreBulkSelection === 'function') {
        window.restoreBulkSelection();
    }
});

\$('.toggle-folders').on('click', function () {
    \$('#folder-list').toggle();
    const icon = \$(this).find('.toggle-icon-folders');
    const isOpen = \$('#folder-list').is(':visible');
    localStorage.setItem('folderListOpen', isOpen);
    icon.toggleClass('fa-caret-down fa-caret-right');
});

\$('.toggle-files').on('click', function () {
    \$('#file-list').toggle();
    \$('.files-section.list-view >.file-card.file-card-header').toggle();
    const icon = \$(this).find('.toggle-icon-files');
    const isOpen = \$('#file-list').is(':visible');
    localStorage.setItem('fileListOpen', isOpen);
    icon.toggleClass('fa-caret-down fa-caret-right');
});

// File title tooltip
(function() {
    var tooltip = document.getElementById('file-title-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'file-title-tooltip';
        tooltip.style.cssText = 'position:fixed;background:#333;color:#fff;padding:5px 8px;border-radius:4px;font-size:11px;white-space:nowrap;z-index:9999;pointer-events:none;opacity:0;transition:opacity 0.2s ease;';
        document.body.appendChild(tooltip);
    }

    \$(document).on('mouseenter', '.file-title[data-title]', function() {
        if (this.scrollWidth <= this.offsetWidth) return;
        var title = \$(this).attr('data-title');
        tooltip.textContent = title;
        tooltip.style.opacity = '1';
        var rect = this.getBoundingClientRect();
        var left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2;
        var top = rect.top - tooltip.offsetHeight - 6;
        if (left < 4) left = 4;
        if (left + tooltip.offsetWidth > window.innerWidth - 4) left = window.innerWidth - tooltip.offsetWidth - 4;
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }).on('mouseleave', '.file-title[data-title]', function() {
        tooltip.style.opacity = '0';
    });
})();

JS);
