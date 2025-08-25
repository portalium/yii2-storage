<?php

use portalium\storage\bundles\StorageAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;
use yii\helpers\Url;

/** @var $this yii\web\View */
/** @var $form portalium\theme\widgets\ActiveForm */
/** @var yii\data\ActiveDataProvider $directoryDataProvider */
/** @var yii\data\ActiveDataProvider $fileDataProvider */
/** @var bool $isPicker */
/** @var string $actionId */

$actionId = $actionId ?? 'index';

StorageAsset::register($this);

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="file-manager">
    <div class="file-controls">
        <?php
        echo Html::beginTag('div', [
            'class' => 'd-flex align-items-center gap-2 flex-wrap'
        ]);

        echo Html::tag(
            'div',
            Html::textInput('file', '', [
                'class' => 'form-control',
                'id' => 'searchFileInput',
                'placeholder' => Module::t('Search file..'),
                'data-is-picker' => $isPicker ? '1' : '0',
            ]) .
            Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), [
                'class' => 'input-group-text'
            ]),
            [
                'class' => 'input-group',
                'style' => 'flex-grow: 1;',
            ]
        );


        echo Html::beginTag('div', ['class' => 'dropdown d-inline']);

        echo Html::button(
            Html::tag('i', '', ['class' => 'fa fa-plus me-2']) .
            Html::tag('span', Module::t('New'), ['class' => 'btn-text']),
            [
                'class' => 'newDropdownClass',
                'type' => 'button',
                'id' => 'newDropdownBtn',
                'data-bs-toggle' => 'dropdown',
                'aria-expanded' => 'false',
            ]
        );


        echo Html::beginTag('ul', ['class' => 'dropdown-menu custom-dropdown-align', 'aria-labelledby' => 'newDropdownBtn']);

        echo Html::tag(
            'li',
            Html::a(
                Html::tag('i', '', ['class' => 'fa fa-folder me-2']) . Module::t('New Folder'),
                '#',
                ['class' => 'dropdown-item', 'onclick' => 'openNewFolderModal(event)', 'id' => 'newFolderBtn']
            )
        );

        echo Html::tag(
            'li',
            Html::a(
                Html::tag('i', '', ['class' => 'fa fa-upload me-2']) . Module::t('Upload File'),
                '#',
                ['class' => 'dropdown-item', 'onclick' => 'uploadFileMenu(event)', 'id' => 'uploadFileBtn']
            )
        );

        echo Html::tag(
            'li',
            Html::a(
                Html::tag('i', '', ['class' => 'fa fa-upload me-2']) . Module::t('Upload Folder'),
                '#',
                ['class' => 'dropdown-item', 'onclick' => 'uploadFolderMenu(event)', 'id' => 'uploadFolderBtn']
            )
        );
        
echo Html::endTag('ul');
echo Html::endTag('div');

echo Html::beginTag('div', [
  'class' => 'view-toggle d-flex align-items-center ms-auto align-self-center mb-0'
]);

        echo Html::button(
            Html::tag('i', '', ['class' => 'fa fa-th me-2']) .
            Html::tag('span', Module::t('Grid View'), ['class' => 'btn-text']),
            [
                'id' => 'btn-grid',
                'class' => 'btn btn-selected btn-sm me-2 d-flex align-items-center',
                'type' => 'button',
                'onclick' => 'setViewMode("grid")',
            ]
        );

        echo Html::button(
            Html::tag('i', '', ['class' => 'fa fa-list me-2']) .
            Html::tag('span', Module::t('List View'), ['class' => 'btn-text']),
            [
                'id' => 'btn-list',
                'class' => 'btn btn-unselected btn-sm d-flex align-items-center',
                'type' => 'button',
                'onclick' => 'setViewMode("list")',
            ]
        );

        echo Html::endTag('div');

        echo Html::endTag('div'); // d-flex align-items-center gap-2 flex-wrap

        echo Html::endTag('div'); // file-controls

        echo Html::beginTag('div', [
            'class' => 'file-list'
        ]);
        Pjax::begin([
            'id' => 'upload-file-pjax',
            'history' => false,
            'timeout' => false,
            'enablePushState' => false,
        ]);
        Pjax::end();

        Pjax::begin([
            'id' => 'new-folder-pjax',
            'history' => false,
            'timeout' => false,
            'enablePushState' => false,
        ]);
        Pjax::end();

        Pjax::begin([
            'id' => 'rename-folder-pjax',
            'history' => false,
            'timeout' => false,
            'enablePushState' => false,
        ]);
        Pjax::end();

        Pjax::begin([
            'id' => 'list-item-pjax',
            'timeout' => false,
            'enablePushState' => false,
            'enableReplaceState' => false,

            'clientOptions' => ['push' => true, 'replace' => false, 'history' => true],
        ]);

        echo $this->render('_item-list', [
            'directoryDataProvider' => $directoryDataProvider,
            'fileDataProvider' => $fileDataProvider,
            'isPicker' => $isPicker ?? false,
            'actionId' => $actionId
        ]);

        Pjax::end();

        Pjax::begin([
            'id' => 'rename-file-pjax',
            'history' => false,
            'timeout' => false,
            'enablePushState' => false
        ]);
        Pjax::end();

        Pjax::begin([
            'id' => 'update-file-pjax',
            'history' => false,
            'timeout' => false,
            'enablePushState' => false
        ]);
        Pjax::end();

        Pjax::begin([
            'id' => 'share-file-pjax',
            'history' => false,
            'timeout' => false,
            'enablePushState' => false
        ]);
        Pjax::end();

        echo Html::endTag('div'); // file-list
        echo Html::endTag('div'); // file-manager
        ?>
<script>
    function applyViewModeClasses(mode) {
    const el  = document.getElementById('files-section');
    const el2 = document.getElementById('folders-section');

    if (el2) {
        el2.classList.remove('grid-view', 'list-view');
        el2.classList.add(mode + '-view');
    }
    if (el) {
        el.classList.remove('grid-view', 'list-view');
        el.classList.add(mode + '-view');

        const row = el.querySelector('.row');
        if (row) {
            row.classList.remove('g-3');
            if (mode === 'grid') row.classList.add('g-3');
        }
    }

    const gridBtn = document.getElementById('btn-grid');
    const listBtn = document.getElementById('btn-list');
    if (gridBtn && listBtn) {
        if (mode === 'grid') {
            gridBtn.classList.remove('btn-unselected'); gridBtn.classList.add('btn-selected');
            listBtn.classList.remove('btn-selected');   listBtn.classList.add('btn-unselected');
        } else {
            listBtn.classList.remove('btn-unselected'); listBtn.classList.add('btn-selected');
            gridBtn.classList.remove('btn-selected');   gridBtn.classList.add('btn-unselected');
        }
    }

    const fileList = document.getElementById('file-list');
    if (fileList) {
        if (mode === 'list') {
            fileList.classList.remove('file-grid', 'mb-3');
        } else {
            fileList.classList.add('file-grid', 'mb-3');
        }
    }
}

function setViewMode(mode) {
    localStorage.setItem('viewMode', mode);
    document.cookie = "viewMode=" + mode + "; path=/; max-age=31536000";

    applyViewModeClasses(mode);

    if (typeof $.pjax !== 'undefined') {
        const currentUrl = window.location.href.split('?')[0];
        $.pjax.reload({
            container: '#pjax-container',
            url: currentUrl + '?viewMode=' + encodeURIComponent(mode),
            push: false,
            replace: false,
            timeout: 10000,
            cache: false
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const savedMode = localStorage.getItem('viewMode') || 'grid';
    console.log(savedMode);
    applyViewModeClasses(savedMode);
});

$(document).on('pjax:end', function () {
    const mode = localStorage.getItem('viewMode') || 'grid';
    applyViewModeClasses(mode);
});

</script>

