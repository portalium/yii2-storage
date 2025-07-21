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

StorageAsset::register($this);

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="file-manager">
<?php
echo Html::beginTag('span', [
    'class' => 'col-md-5 d-flex gap-2 mb-3'
]);

echo Html::tag(
    'span',
    Html::textInput('file', '', [
        'class' => 'form-control',
        'id' => 'searchFileInput',
        'placeholder' => Module::t('Search file..'),
        'data-is-picker' => $isPicker ? '1' : '0',
    ]) .
        Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), [
            'class' => 'input-group-text'
        ]),
    ['class' => 'input-group']
);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-upload me-2', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('Upload'), ['class' => 'btn-text']),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-success btn-md d-flex',
        'onclick' => 'openUploadModal(event)',
        'id' => 'uploadBtn',
    ],
]);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-folder me-2', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('New Folder'), ['class' => 'btn-text']),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-primary btn-md d-flex',
        'style' => 'min-width: 106px;',
        'onclick' => 'openNewFolderModal(event)',
        'id' => 'newFolderBtn',
    ],
]);
echo Html::endTag('span');

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

?>
</div>
