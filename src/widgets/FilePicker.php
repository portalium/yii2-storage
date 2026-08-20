<?php

namespace portalium\storage\widgets;

use Yii;
use portalium\widgets\Pjax;
use yii\helpers\Url;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\InputWidget;
use portalium\data\ActiveDataProvider;


class FilePicker extends InputWidget
{
    public $dataProvider;
    public $multiple = 0;
    public $isJson = 1;
    public $callbackName = null;
    public $manage = false;
    public $fileExtensions = null;
    public $allowedExtensions = null;
    public $attributes = ['id_storage'];
    public $isPicker = true;
    public $allowFolderSelection = false;
    public $showPreview = true;

    public function init(): void
    {
        parent::init();
        Yii::$app->view->registerJs('$.pjax.defaults.timeout = 30000;');

        $this->multiple = $this->options['multiple'] ?? $this->multiple;
        $this->isJson = $this->options['isJson'] ?? $this->isJson;
        $this->callbackName = $this->options['callbackName'] ?? $this->callbackName;
        $this->fileExtensions = $this->options['fileExtensions'] ?? $this->fileExtensions;
        $this->allowedExtensions = $this->options['allowedExtensions'] ?? $this->allowedExtensions;
        $this->isPicker = $this->options['isPicker'] ?? $this->isPicker;
        $this->allowFolderSelection = $this->options['allowFolderSelection'] ?? $this->allowFolderSelection;
        $this->showPreview = $this->options['showPreview'] ?? $this->showPreview;

        if (isset($this->options['attributes'])) {
            $this->attributes = $this->options['attributes'];
        } elseif (isset($_GET['attributes'])) {
            $this->attributes = is_string($_GET['attributes']) ?
                explode(',', $_GET['attributes']) : $_GET['attributes'];
        } elseif (isset($_POST['attributes'])) {
            $this->attributes = is_string($_POST['attributes']) ?
                explode(',', $_POST['attributes']) : $_POST['attributes'];
        }

        if (!is_array($this->attributes)) {
            $this->attributes = [$this->attributes];
        }

        $this->attributes = array_filter($this->attributes, function ($attr) {
            return !empty(trim($attr));
        });

        if (empty($this->attributes)) {
            $this->attributes = ['id_storage'];
        }
    }

    public function run()
    {
        \portalium\storage\bundles\FilePickerAsset::register($this->view);

        $query = Storage::find();

        if (is_array($this->fileExtensions) && !empty($this->fileExtensions)) {
            $orConditions = ['or'];
            foreach ($this->fileExtensions as $extension) {
                $orConditions[] = ['like', 'name', $extension];
            }
            $query->andWhere($orConditions);
        }

        $this->dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 12],
        ]);

        if ($this->hasModel()) {
            echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        }

        $realAttribute = $this->attribute;

        if (preg_match('/\](\w+)$/', $this->attribute ?? '', $matches)) {
            $realAttribute = $matches[1];
        } elseif (preg_match('/^(\w+)/', $this->attribute ?? '', $matches)) {
            $realAttribute = $matches[1];
        }

        $value = $this->model->{$realAttribute} ?? '';
        $decoded = json_decode($value, true);
        $idStorage = '';

        if ($this->multiple && is_array($decoded)) {
            $first = reset($decoded);
            $idStorage = is_array($first) ? ($first['id_storage'] ?? '') : $first;
        } elseif (!empty($decoded)) {
            $idStorage = is_array($decoded) ? ($decoded['id_storage'] ?? '') : $decoded;
        }
        
        echo Html::hiddenInput('preview-file-' . $this->options['id'], $value, ['id' => 'preview-file-' . $this->options['id']]);

        echo Html::button('<span class="btn-text">' . Module::t('Select File') . '</span>', [
            'class' => 'btn btn-primary',
            'data-allowed-extensions' => json_encode($this->allowedExtensions ?? []),
            'onclick' => 'handleFilePickerClick(this, "' . $this->options['id'] . '", "' . $idStorage . '", ' . ($this->multiple ? 'true' : 'false') . ', ' . ($this->isJson ? 'true' : 'false') . ', "' . ($this->callbackName ?? '') . '", ' . ($this->isPicker ? 'true' : 'false') . ', ' . json_encode($this->attributes) . ', ' . json_encode($this->allowedExtensions ?? []) . ', ' . ($this->allowFolderSelection ? 'true' : 'false') . ')'
        ]);

        if ($this->showPreview) {
            echo Html::button('<span class="btn-text">' . Module::t('Preview File') . '</span>', [
                'class' => 'btn btn-primary ms-2',
                'onclick' => 'previewSelectedFile("' . $this->options['id'] . '")',
                'style' => 'margin-right: 5px;'
            ]);
        }

        $modalHtml = $this->render('@portalium/storage/views/web/default/_filePreviewModal');

        if (empty($this->view->params['storageFilePreviewModalRegistered'])) {
            $this->view->params['storageFilePreviewModalRegistered'] = true;

            $js = '(function(){'
                . 'if (!document.getElementById("file-preview-modal") && !window._storageFilePreviewModalRegistered) {'
                . 'window._storageFilePreviewModalRegistered = true;'
                . 'document.body.insertAdjacentHTML("beforeend", ' . json_encode($modalHtml) . ');'
                . '}'
                . '})();';

            $this->view->registerJs($js, \yii\web\View::POS_END);
        }

        $this->registerJsScript();
    }

    protected function registerJsScript()
    {
        $config = [
            'urls' => [
                'getFileAttributes' => Url::to(['/storage/default/get-file-attributes']),
                'downloadFile' => Url::to(['/storage/default/download-file']),
                'copyFile' => Url::to(['/storage/default/copy-file']),
                'deleteFile' => Url::to(['/storage/default/delete-file']),
                'deleteFolder' => Url::to(['/storage/default/delete-folder']),
                'pickerModal' => Url::to(['/storage/default/picker-modal']),
                'pickerContent' => Url::to(['/storage/default/picker-content']),
                'index' => Url::to(['/storage/default/index']),
            ],
            't' => [
                'error' => Module::t('Error'),
                'close' => Module::t('Close'),
                'fileMissing' => Module::t('The file record exists but the file could not be found on the server.'),
                'fileLoadFailed' => Module::t('File could not be loaded.'),
                'fileLoadError' => Module::t('An error occurred while loading the file.'),
                'confirmDeleteFile' => Module::t('Are you sure you want to delete it?'),
                'confirmDeleteFolder' => Module::t('Are you sure you want to delete the folder?'),
                'openingShare' => Module::t('Opening the share screen...'),
            ],
        ];

        $this->view->registerJs(
            'window.FilePickerConfig = ' . json_encode($config) . ';'
            . 'window.fileExtensions = ' . json_encode($this->fileExtensions ?? []) . ';'
            . 'window.isPicker = ' . ($this->isPicker ? 'true' : 'false') . ';'
            . 'window.allowFolderSelection = ' . ($this->allowFolderSelection ? 'true' : 'false') . ';',
            \yii\web\View::POS_BEGIN
        );
    }
}
