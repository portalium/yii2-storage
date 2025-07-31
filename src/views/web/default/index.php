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
        'style' => 'min-width: 300px; max-width: 400px; flex-grow: 1;',
    ]
);

echo Html::beginTag('div', ['class' => 'dropdown d-inline']);

echo Html::button(
    Html::tag('i', '', ['class' => 'fa fa-plus me-2']) .
    Html::tag('span', Module::t('New'), ['class' => 'btn-text']),
    [
        'class' => 'btn btn-primary btn-md d-flex align-items-center',
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

echo Html::endTag('div');
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
echo Html::endTag('div'); // file-list

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

?>
</div>
