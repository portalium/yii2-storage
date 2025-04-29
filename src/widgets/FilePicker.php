<?php

namespace portalium\storage\widgets;

use Yii;
use portalium\widgets\Pjax;
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

    public function init()
    {
        parent::init();
        Yii::$app->view->registerJs('$.pjax.defaults.timeout = 30000;');

        $this->multiple = $this->options['multiple'] ?? $this->multiple;
        $this->isJson = $this->options['isJson'] ?? $this->isJson;
        $this->callbackName = $this->options['callbackName'] ?? $this->callbackName;
        $this->fileExtensions = $this->options['fileExtensions'] ?? $this->fileExtensions;
    }

    public function run()
    {
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

        $value = $this->model->{$this->attribute} ?? '';
        $decoded = json_decode($value, true);
        $idStorage = '';

        if ($this->multiple && is_array($decoded)) {
            $first = reset($decoded);
            $idStorage = is_array($first) ? ($first['id_storage'] ?? '') : $first;
        } elseif (!empty($decoded)) {
            $idStorage = is_array($decoded) ? ($decoded['id_storage'] ?? '') : $decoded;
        }

        echo Html::script("window.fileExtensions = " . json_encode($this->fileExtensions ?? []) . ";");

        echo Html::button(Module::t('Select File'), [
            'class' => 'btn btn-primary',
            'onclick' => 'openFilePickerModal("' . $this->options['id'] . '", "' . $idStorage . '", ' . ($this->multiple ? 'true' : 'false') . ', ' . ($this->isJson ? 'true' : 'false') . ', "' . ($this->callbackName ?? '') . '")'
        ]);

        Pjax::begin([
            'id' => $this->options['id'] . '-pjax',
            'enablePushState' => false,
            'timeout' => 50000,
        ]);

        $this->registerJsScript();

        Pjax::end();
    }

    protected function registerJsScript()
    {
        $js = <<<JS
const updateFileCard = function(id_storage) {
    $('.file-card.active').removeClass('active');
    $('.file-card input[type="checkbox"]').prop('checked', false);
    if (!id_storage) return;

    if (Array.isArray(id_storage)) {
        id_storage.forEach(id => {
            let el = $('#file-picker-modal span[data-id="' + id + '"]');
            el.addClass('active');
            el.find('input[type="checkbox"]').prop('checked', true);
        });
    } else {
        let el = $('#file-picker-modal span[data-id="' + id_storage + '"]');
        el.addClass('active');
        el.find('input[type="checkbox"]').prop('checked', true);
    }
};

const showModal = function(id) {
    setTimeout(() => {
        let modal = new bootstrap.Modal(document.getElementById('file-picker-modal'));
        modal.show();
        window.inputId = id;

        $(document).on('click', '#file-picker-modal .pagination a', function(e) {
            e.preventDefault();
            $.pjax.reload({
                container: '#' + id + '-pjax',
                url: $(this).attr('href'),
                type: 'GET',
                data: {
                    id: id,
                    multiple: window.multiple,
                    isJson: window.isJson,
                    fileExtensions: window.fileExtensions
                },
                push: false,
                replace: false
            }).done(() => {
                showModal(id);
            });
        });
    }, 500);
};

if (!window.openFilePickerModal) {
    window.openFilePickerModal = function(id, id_storage, multiple, isJson, callbackName) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;

        if ($('#file-picker-modal').length === 0) {
            $.pjax.reload({
                container: '#' + id + '-pjax',
                url: '/storage/default/picker-modal',
                type: 'GET',
                data: {
                    id: id,
                    multiple: multiple,
                    isJson: isJson,
                    fileExtensions: window.fileExtensions
                }
            }).done(() => {
                updateFileCard(id_storage);
                showModal(id);
            });
        } else {
            updateFileCard(id_storage);
            showModal(id);
        }
    };
}

if (!window.saveSelect) {
    window.saveSelect = function () {
        let selectedFiles = window.multiple
            ? $('.file-card input[type="checkbox"]:checked').map(function () {
                return $(this).closest('.file-card').data('id');
            }).get()
            : $('.file-card.active').data('id');

        let value = window.isJson
            ? (window.multiple
                ? JSON.stringify(selectedFiles.map(id => ({ id_storage: id })))
                : JSON.stringify({ id_storage: selectedFiles }))
            : (window.multiple
                ? selectedFiles.join(',')
                : selectedFiles);

        $('#' + window.inputId).val(value);

        if (window.callbackName && typeof window[window.callbackName] === 'function') {
            window[window.callbackName](selectedFiles);
        }

        $('#file-picker-modal').modal('hide');
    };
}
JS;

        $this->view->registerJs($js, \yii\web\View::POS_BEGIN);
    }
}
