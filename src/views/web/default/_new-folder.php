<?php

use portalium\storage\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Modal;

Modal::begin([
    'id' => 'newFolderModal',
    'title' => Module::t('Create New Folder'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'closeButton' => false,
    'footer' =>
        Button::widget([
            'label' => Module::t('Close'),
            'options' => [
                'class' => 'btn btn-danger',
                'data-bs-dismiss' => 'modal'
            ],
        ]) . ' ' .
        Button::widget([
            'label' => Module::t('Create'),
            'options' => [
                'class' => 'btn btn-success',
                'id' => 'createFolderButton',
                'type' => 'button',
            ],
        ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered']
]);

$form = ActiveForm::begin([
    'id' => 'newFolderForm',
    'options' => ['data-pjax' => true],
    'method' => 'post'
]);

echo '<div class="mb-3">
        <label for="folderName" class="form-label">' . Module::t('Folder Name') . '</label>
        <input type="text" class="form-control" id="folderName" name="folderName" placeholder="' . Module::t('Enter folder name') . '">
      </div>';

echo '<div class="mb-3">
        <label for="folderType" class="form-label">' . Module::t('Select Directory') . '</label>
        <select class="form-select" id="folderType" name="folderType">
            <option selected disabled>' . Module::t('Select folder type') . '</option>
            <option value="default">' . Module::t('Root') . '</option>
            <option value="shared">' . Module::t('ExampleDirectory1') . '</option>
        </select>
      </div>';

ActiveForm::end();
Modal::end();
