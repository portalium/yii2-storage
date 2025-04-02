<?php

use portalium\storage\bundles\FilePickerAsset;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
use yii\helpers\Html;
use yii\bootstrap5\Modal;
use portalium\theme\widgets\ListView;
use portalium\theme\widgets\Button;
use yii\bootstrap5\Dropdown;




FilePickerAsset::register($this);

/* @var $this yii\web\View */
/* @var $form portalium\theme\widgets\ActiveForm */
/* @var $model portalium\storage\models\Storage */

$this->title = Module::t('TEST');
$this->params['breadcrumbs'][] = $this->title;

?>
<?php

$form = ActiveForm::begin([
    'action' => ['index'],
    'method' => 'get',
]);

echo Html::beginTag('span', ['class' => 'col-md-4 d-flex gap-2']);


echo Html::tag('span',
    Html::textInput('file', '', [
        'class' => 'form-control',
        'placeholder' => Module::t('Search file..')
    ]) .
    Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), ['class' => 'input-group-text']),
    ['class' => 'input-group']
);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-upload', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('Upload'), ['class' => 'ms-2']),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-success btn-md d-flex',
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#uploadModal',
    ],
]);

echo Html::endTag('span');

?>

<?php

Modal::begin([
    'id' => 'uploadModal',
    'title' => Module::t('Upload File'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'footer' => Button::widget([
        'label' => Module::t('Close'),
        'options' => [
            'class' => 'btn btn-danger',
            'data-bs-dismiss' => 'modal',
        ],
    ]) . ' ' . Button::widget([
        'label' => Html::tag('i', '', ['class' => 'fa fa-cloud-upload-alt']) . ' ' . Module::t('Upload'),
        'encodeLabel' => false,
        'options' => [
            'class' => 'btn btn-success',
            'id' => 'uploadButton',
            'type' => 'submit',
        ],
    ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
    'closeButton' => false,
]);

$form = ActiveForm::begin([
    'id' => 'uploadForm',
    'options' => ['enctype' => 'multipart/form-data'],
]);

echo Html::label(Module::t('Title'), 'uploadTitle', ['class' => 'form-label']);
echo Html::textInput('title', '', ['class' => 'form-control', 'id' => 'uploadTitle', 'required' => true]);

echo Html::label(Module::t('Select file'), 'uploadFileInput', ['class' => 'form-label']);
echo Html::fileInput('file', '', ['class' => 'form-control', 'id' => 'uploadFileInput']);

ActiveForm::end();

Modal::end();

?>

<hr />

<?php


echo Html::beginTag('span', ['class' => 'file-card col-md-2']);
    echo Html::beginTag('span', ['class' => 'card-header']);
        echo 'DosyaA';
        echo Html::tag('i', '', ['class' => 'fa fa-ellipsis-h', 'id' => 'menu-trigger']);
        echo Dropdown::widget([
            'items' => array_map(function ($item) {
                return [
                    'label' => Html::tag('i', '', ['class' => 'fa ' . $item['icon']]) . ' ' . Module::t($item['label']),
                    'url' => $item['url'] ?? '#',
                    'encode' => false,
                    'linkOptions' => [
                        'onclick' => $item['onclick'] ?? null,
                        'data-bs-toggle' => $item['dataBsToggle'] ?? null,
                        'data-bs-target' => $item['dataBsTarget'] ?? null,
                        'download-url' => $item['downloadUrl'] ?? null,
                    ],
                ];
            }, [
                ['icon' => 'fa-download', 'label' => 'Download', 'onclick' => 'downloadItem(this)', 'downloadUrl' => '/portalium/data'],
                ['icon' => 'fa-pencil', 'label' => 'Rename', 'dataBsToggle' => 'modal', 'dataBsTarget' => '#renameModal'],
                ['icon' => 'fa-refresh', 'label' => 'Update', 'dataBsToggle' => 'modal', 'dataBsTarget' => '#updateModal'],
                ['icon' => 'fa-arrows-alt', 'label' => 'Move'],
                ['icon' => 'fa-share-alt', 'label' => 'Share', 'dataBsToggle' => 'modal', 'dataBsTarget' => '#shareModal'],
                ['icon' => 'fa-copy', 'label' => 'Make a Copy'],
                ['icon' => 'fa-trash', 'label' => 'Remove']
            ]),
            'options' => ['class' => 'dropdown-menu show', 'id' => 'context-menu'],
        ]);
    echo Html::endTag('span');
    echo Html::img('https://img.icons8.com/ios/452/pdf.png', ['alt' => 'PDF']);
echo Html::endTag('span');

?>

<?php

Modal::begin([
    'id' => 'renameModal',
    'title' => Module::t('Rename'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'footer' => Button::widget([
        'label' => Module::t('Close'),
        'options' => [
            'class' => 'btn btn-danger',
            'data-bs-dismiss' => 'modal',
        ],
    ]) . ' ' . Button::widget([
        'label' => Module::t('Rename'),
        'options' => [
            'class' => 'btn btn-success',
            'id' => 'renameButton',
            'type' => 'submit',
        ],
    ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
    'closeButton' => false,
]);

$form = ActiveForm::begin([
    'id' => 'renameForm',
    'options' => ['enctype' => 'multipart/form-data'],
]);

echo Html::label(Module::t('New Name'), 'renameInput', ['class' => 'form-label']);
echo Html::textInput('newTitle', '', [
    'class' => 'form-control',
    'id' => 'renameInput',
    'placeholder' => Module::t('Enter new name'),
]);

ActiveForm::end();

Modal::end();

?>

<?php

Modal::begin([
    'id' => 'updateModal',
    'title' => Module::t('Update'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'footer' => Button::widget([
        'label' => Module::t('Close'),
        'options' => [
            'class' => 'btn btn-danger',
            'data-bs-dismiss' => 'modal',
        ],
    ]) . ' ' . Button::widget([
        'label' => Module::t('Update'),
        'options' => [
            'class' => 'btn btn-success',
            'id' => 'updateButton',
            'type' => 'submit',
        ],
    ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
    'closeButton' => false,
]);

$form = ActiveForm::begin([
    'id' => 'updateForm',
    'options' => ['enctype' => 'multipart/form-data'],
]);

echo Html::label(Module::t('Select file'), 'updateFileInput', ['class' => 'form-label']);
echo Html::fileInput('file', '', [
    'class' => 'form-control',
    'id' => 'updateFileInput',
]);

ActiveForm::end();

Modal::end();

?>

<?php

$users = [
    ['name' => 'Sümeyye Demir', 'email' => 'sumeyye.demir@example.com'],
    ['name' => 'Ahmet Yılmaz', 'email' => 'ahmet.yilmaz@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ];


Modal::begin([
    'id' => 'shareModal',
    'title' => Module::t('Share Access'),
    'size' => Modal::SIZE_DEFAULT,
    'footer' => Button::widget([
        'label' => Html::tag('i', '', ['class' => 'fa fa-link me-2']) . Module::t('Copy Link'),
        'encodeLabel' => false,
        'options' => [
            'class' => 'btn btn-outline-secondary',
            'id' => 'copyLink',
            'data-copied' => htmlspecialchars(Module::t('Copied!'), ENT_QUOTES, 'UTF-8'),
            'style' => 'float: left; margin-right: 300px;',
        ],
    ]) . ' ' . Button::widget([
        'label' => Module::t('Done'),
        'options' => [
            'class' => 'btn btn-success',
            'type' => 'submit',
            'form' => 'shareForm',
            'id' => 'doneButton',
        ],
    ]),
]);


$form = ActiveForm::begin([
    'id' => 'shareForm',
    'options' => ['class' => 'mb-3'],
]);

echo Html::textInput('searchUser', '', [
    'class' => 'form-control form-control-lg',
    'id' => 'searchUser',
    'placeholder' => Module::t('Add person or group'),
]);

ActiveForm::end();

echo Html::tag('h6', Module::t('Erişimi olan kişiler'), ['class' => 'fw-bold mb-3 text-secondary']);

echo Html::beginTag('span', ['class' => 'list-group list-group-flush rounded-3', 'style' => 'max-height: 300px; overflow-y: auto;']);
foreach ($users as $user) {
    echo Html::tag('span',
        Html::tag('span',
            Html::tag('i', '', ['class' => 'fa fa-user me-2', 'style' => 'font-size: 20px;']) .
            Html::tag('span',
                Html::tag('h6', $user['name'], ['class' => 'mb-0 fw-semibold']) .
                Html::tag('small', $user['email'], ['class' => 'text-muted d-block']),
                ['class' => 'd-flex flex-column ms-2']
            ),
            ['class' => 'd-flex align-items-center']
        ) .
        Button::widget([
            'label' => Html::tag('i', '', ['class' => 'fa fa-trash']),
            'encodeLabel' => false,
            'options' => [
                'class' => 'btn btn-light text-danger',
                'title' => 'Sil',
            ],
        ]),
        ['class' => 'd-flex justify-content-between align-items-center p-2']
    );
}
echo Html::endTag('span');


echo Html::label(Module::t('General Access'), 'accessLevel', ['class' => 'fw-bold mb-3 text-secondary ms-3 mt-4']);
echo Html::dropDownList('accessLevel', 'private', [
    'private' => Module::t('Restricted'),
    'public' => Module::t('Public')
], [
    'class' => 'form-select form-select-lg mt-4',
    'id' => 'accessSelect'
]);

Modal::end();

?>

<?php ActiveForm::end(); ?>