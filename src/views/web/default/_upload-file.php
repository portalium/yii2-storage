<?php

use portalium\storage\models\Storage;
use portalium\storage\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Html;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */
/* @var $form yii\widgets\ActiveForm */

Modal::begin([
    'id' => 'uploadModal',
    'title' => Module::t('Upload File'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'closeButton' => false,
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
                'type' => 'button',
            ],
        ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
]);

$form = ActiveForm::begin([
    'id' => 'uploadForm',
    'action' => '/storage/default/upload-file',
    'method' => 'post',
    'options' => ['enctype' => 'multipart/form-data', 'data-pjax' => true]
]);

echo $form->field($model, 'type')->dropDownList([
    'file'   => Module::t('File'),
    'folder' => Module::t('Folder'),
], ['id' => 'upload-type']);

echo Html::beginTag('div', ['id' => 'title-field-wrapper']);
if ($model instanceof Storage) {
    echo $form->field($model, 'title')->textInput(['required' => true]);
}
echo Html::endTag('div');

if ($model instanceof Storage) {
    echo $form->field($model, 'file[]')->fileInput([
        'id' => 'file-input',
        'required' => true,
    ]);
} else {
    echo Html::fileInput('Storage[file][]', null, [
        'id'           => 'file-input',
        'required'     => true,
        'multiple'     => true,
        'webkitdirectory' => true,
        'directory'    => true,
    ]);
}

ActiveForm::end();
Modal::end();
?>

<?php
$this->registerJs(<<<JS
(function () {
    var uploadType        = document.getElementById('upload-type'),
        fileInput         = document.getElementById('file-input'),
        titleFieldWrapper = document.getElementById('title-field-wrapper'),
        titleInput        = document.querySelector('[name="Storage[title]"]');

    if (!uploadType || !fileInput || !titleFieldWrapper || !titleInput) return;

    function updateInputAttributes() {
        if (uploadType.value === 'folder') {
            fileInput.setAttribute('multiple', '');
            fileInput.setAttribute('webkitdirectory', '');
            fileInput.setAttribute('directory', '');
            titleFieldWrapper.style.display = 'none';
            titleInput.disabled = true;
        } else {
            fileInput.removeAttribute('multiple');
            fileInput.removeAttribute('webkitdirectory');
            fileInput.removeAttribute('directory');
            titleFieldWrapper.style.display = '';
            titleInput.disabled = false;
        }
    }

    uploadType.addEventListener('change', updateInputAttributes);
    updateInputAttributes();
})();
JS
);

?>
