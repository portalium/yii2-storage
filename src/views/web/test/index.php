<?php

use portalium\storage\bundles\FilePickerAsset;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
use yii\helpers\Html;
use yii\bootstrap5\Modal;
use portalium\theme\widgets\Button;
use yii\bootstrap5\Dropdown;
use portalium\theme\widgets\ListView;

FilePickerAsset::register($this);

/* @var $this yii\web\View */
/* @var $form portalium\theme\widgets\ActiveForm */
/* @var $model portalium\storage\models\Storage */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Module::t('TEST');
$this->params['breadcrumbs'][] = $this->title;

?>
<?php

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

    <hr/>

<?php

echo ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => function ($model) {

        $content = Html::beginTag('span', ['class' => 'file-card col-md-2', 'style' => 'margin: 5px; margin-bottom: 20px;']);
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
                        'onclick' => '',
                        'download-url' => '/portalium/data/' . $model->id_storage,
                    ],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-pencil']) . ' ' . Module::t('Rename'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#renameModal',
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
                    'linkOptions' => [],
                ],
                [
                    'label' => Html::tag('i', '', ['class' => 'fa fa-trash']) . ' ' . Module::t('Remove'),
                    'url' => '#',
                    'encode' => false,
                    'linkOptions' => [],
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
            'style' => 'width:100px; display:block; margin:10px auto;'
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
    'title' => Module::t('Share'),
    'size' => Modal::SIZE_DEFAULT,
    'closeButton' => false,
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
    'footer' => Button::widget([
            'label' => Html::tag('i', '', ['class' => 'fa fa-link me-2']) . Module::t('Copy Link'),
            'encodeLabel' => false,
            'options' => [
                'class' => 'btn btn-outline-secondary',
                'id' => 'copyLink',
                'data-copied' => htmlspecialchars(Module::t('Copied!'), ENT_QUOTES, 'UTF-8'),
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

echo Html::textInput('searchUser', '', [
    'class' => 'form-control form-control-lg mb-3',
    'id' => 'searchUser',
    'placeholder' => Module::t('Add person or group'),
]);
echo Html::tag('h6', Module::t('People with access'), ['class' => 'fw-bold mb-3 text-secondary']);

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

echo Html::label(Module::t('General Access'), null, ['class' => 'fw-bold mb-3 text-secondary mt-4']);

echo Html::tag('div',
    Html::tag('div',
        Html::tag('div',
            Html::tag('i', '', [
                'class' => 'access-icon fa fa-lock bg-light rounded-circle p-2',
                'style' => 'font-size: 18px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;'
            ]) .
            Html::tag('div',
                Html::tag('span', Module::t('Restricted'), [
                    'class' => 'access-text fw-semibold d-block',
                    'data-public' => Module::t('Public'),
                    'data-private' => Module::t('Restricted')
                ]) .
                Html::tag('small', Module::t('Only people with access can open it using this link'), [
                    'class' => 'access-desc text-muted d-block',
                    'data-public' => Module::t('Anyone with the link can view the content'),
                    'data-private' => Module::t('Only people with access can open it using this link')
                ]),
                ['class' => 'ms-3']
            ),
            ['class' => 'd-flex align-items-center']
        ),
        ['class' => 'mb-3']
    ) .
    Html::dropDownList('accessLevel', 'private', [
        'private' => Module::t('Restricted'),
        'public' => Module::t('Public')
    ], [
        'class' => 'access-select form-select form-select-lg mt-4'
    ]),
    ['class' => 'file-access']
);


Modal::end();

?>
