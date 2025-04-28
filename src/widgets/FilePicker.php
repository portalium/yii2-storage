<?php

namespace portalium\storage\widgets;

use Yii;
use portalium\widgets\Pjax;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\InputWidget;
use yii\data\ActiveDataProvider;

class FilePicker extends InputWidget
{
    public $dataProvider;
    public $multiple = 0;
    public $isJson = 1;
    public $callbackName = null;
    public $fileExtensions = null;
    public $manage = false;

    public function init()
    {
        parent::init();

        if (isset($this->options['multiple'])) {
            $this->multiple = $this->options['multiple'];
        }

        if (isset($this->options['isJson'])) {
            $this->isJson = $this->options['isJson'];
        }

        if (isset($this->options['callbackName'])) {
            $this->callbackName = $this->options['callbackName'];
        }

        if (isset($this->options['fileExtensions'])) {
            $this->fileExtensions = $this->options['fileExtensions'];
        }
    }

    public function run()
    {
        $query = Storage::find();
        if ($this->fileExtensions) {
            foreach ($this->fileExtensions as $ext) {
                $query->orWhere(['like', 'name', $ext]);
            }
        }

        $this->dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 12,
            ],
        ]);

        if ($this->hasModel()) {
            echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        }

        $value = $this->model->{$this->attribute} ?? '';
        $decoded = [];

        if (!empty($value)) {
            $decoded = json_decode($value, true);
        }

        if ($this->multiple && is_array($decoded)) {
            $first = reset($decoded);
            $idStorage = is_array($first) ? ($first['id_storage'] ?? '') : $first;
        } else {
            $idStorage = is_array($decoded) ? ($decoded['id_storage'] ?? '') : $decoded;
        }

        echo Html::button(Module::t('Select File'), [
            'class' => 'btn btn-primary',
            'onclick' => 'openFilePickerModal("' . $this->options['id'] . '", "' . $idStorage . '", ' . $this->multiple . ', ' . $this->isJson . ', "' . ($this->callbackName ?? '') . '")'
        ]);

        echo '<div id="' . $this->options['id'] . '-container"></div>';

        $js = <<<JS
const updateFileCard = function(id_storage) {
    $('.file-card.active').removeClass('active');
    $('.file-card input[type="checkbox"]').prop('checked', false);

    if (id_storage) {
        if (Array.isArray(id_storage)) {
            id_storage.forEach(function(id) {
                $('#file-picker-modal span[data-id="' + id + '"]').addClass('active');
                $('#file-picker-modal span[data-id="' + id + '"] input[type="checkbox"]').prop('checked', true);
            });
        } else {
            $('#file-picker-modal span[data-id="' + id_storage + '"]').addClass('active');
            $('#file-picker-modal span[data-id="' + id_storage + '"] input[type="checkbox"]').prop('checked', true);
        }
    }
};

const getSelectedIds = function() {
    let val = $('#' + window.inputId).val();
    if (val && window.isJson) {
        let parsed = JSON.parse(val);
        return parsed.map(item => item.id_storage);
    } else if (val) {
        return val.split(',');
    }
    return [];
};

const getSelectedId = function() {
    let val = $('#' + window.inputId).val();
    if (val && window.isJson) {
        let parsed = JSON.parse(val);
        return parsed.id_storage;
    } else {
        return val;
    }
};

if (window.openFilePickerModal === undefined) {
    window.openFilePickerModal = function (id, id_storage, multiple, isJson, callbackName) {
        window.inputId = id;
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;

        $.get('/storage/default/picker-modal', { 
            id: id,
            multiple: multiple,
            isJson: isJson
        }, function(data) {
            $('#' + id + '-container').html(data);

            var modal = new bootstrap.Modal(document.getElementById('file-picker-modal'));
            modal.show();

            $(document).off('click', '#file-picker-modal .pagination a');
            $(document).on('click', '#file-picker-modal .pagination a', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                $.get(url, function(content) {
                    $('#file-picker-modal .modal-body').html($(content).find('.modal-body').html());
                    updateFileCard(window.multiple ? getSelectedIds() : getSelectedId());
                });
            });

            updateFileCard(id_storage);
        });
    };
}

if (window.saveSelect === undefined) {
    window.saveSelect = function () {
        let selectedFiles = [];
        let selectedFile = null;

        if (window.multiple) {
            $('.file-card input[type="checkbox"]:checked').each(function () {
                selectedFiles.push($(this).closest('.file-card').data('id'));
            });

            if (window.isJson) {
                var jsonResult = selectedFiles.map(function(id) {
                    return { id_storage: id };
                });
                $('#' + window.inputId).val(JSON.stringify(jsonResult));
            } else {
                $('#' + window.inputId).val(selectedFiles.join(','));
            }
        } else {
            selectedFile = $('.file-card.active').data('id');

            if (window.isJson) {
                $('#' + window.inputId).val(JSON.stringify({id_storage: selectedFile}));
            } else {
                $('#' + window.inputId).val(selectedFile);
            }
        }

        if (window.callbackName && typeof window[window.callbackName] === 'function') {
            if (window.multiple) {
                window[window.callbackName](selectedFiles);
            } else {
                window[window.callbackName](selectedFile);
            }
        }

        var modalEl = document.getElementById('file-picker-modal');
        var modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
        }
    };
}
JS;

        $this->view->registerJs($js, \yii\web\View::POS_END);
    }
}

