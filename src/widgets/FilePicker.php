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
        echo Html::script("window.isPicker = true;");

        echo Html::button(Module::t('Select File'), [
            'class' => 'btn btn-primary',
            'onclick' => 'window.openFilePickerModal("' . $this->options['id'] . '", "' . $idStorage . '", ' . ($this->multiple ? 'true' : 'false') . ', ' . ($this->isJson ? 'true' : 'false') . ', "' . ($this->callbackName ?? '') . '")'
        ]);

        $this->registerJsScript();
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

const forceReflow = function() {
    const container = document.querySelector('#file-picker-modal .files-container');
    if (container) {
        container.classList.remove('d-none');
        void container.offsetWidth;
        container.classList.add('d-flex');
    }
};

const cleanupModal = function() {
    const modalEl = document.getElementById('file-picker-modal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
};

const bindModalButtons = function() {
    $(document).off('click.btn-select').on('click.btn-select', '#file-picker-modal .btn-select', function() {
        window.saveSelect();
    });

    $(document).off('click.btn-close').on('click.btn-close', '#file-picker-modal .btn-close, #file-picker-modal .close', function() {
        cleanupModal();
    });
};

if (!window.openFilePickerModal) {
    window.openFilePickerModal = function(id, id_storage, multiple, isJson, callbackName) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;
        window.inputId = id;

        cleanupModal();

        $.ajax({
            url: '/storage/default/picker-modal',
            type: 'GET',
            data: {
                id: id,
                multiple: multiple,
                isJson: isJson,
                fileExtensions: window.fileExtensions
            },
            success: function(response) {
                $('#file-picker-modal').remove();
                $('body').append(response);

                requestAnimationFrame(() => {
                    const modalEl = document.getElementById('file-picker-modal');
                    const modal = new bootstrap.Modal(modalEl, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    document.body.classList.add('modal-open');
                    document.body.style.paddingRight = '0px';
                    modal.show();

                    updateFileCard(id_storage);
                    bindModalButtons();
                    forceReflow();
                });

                $(document).off('click.pjax-pagination').on('click.pjax-pagination', '#file-picker-modal .pagination a', function(e) {
                    e.preventDefault();
                    $('#file-picker-modal .modal-content').append('<div class="loading-overlay"><div class="spinner"></div></div>');

                    $.ajax({
                        url: $(this).attr('href'),
                        type: 'GET',
                        data: {
                            id: window.inputId,
                            multiple: window.multiple,
                            isJson: window.isJson,
                            fileExtensions: window.fileExtensions
                        },
                        success: function(newContent) {
                            const \$temp = $('<div></div>').append(newContent);
                            const newGrid = \$temp.find('.files-container').html();
                            const newPagination = \$temp.find('.pagination-container').html();
                            $('#file-picker-modal .files-container').html(newGrid);
                            $('#file-picker-modal .pagination-container').html(newPagination);
                            $('.loading-overlay').remove();
                            updateFileCard(id_storage);
                            forceReflow();
                        },
                        error: function() {
                            $('.loading-overlay').remove();
                            alert('Sayfa yüklenirken bir hata oluştu.');
                        }
                    });
                });
            },
            error: function() {
                alert('Modal yüklenirken bir hata oluştu.');
            }
        });
    };
}

if (!window.saveSelect) {
    window.saveSelect = function() {
        let selectedFiles = window.multiple ?
            $('.file-card input[type="checkbox"]:checked').map(function() {
                return $(this).closest('.file-card').data('id');
            }).get() :
            $('.file-card.active').data('id');

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

        cleanupModal();
    };
}
JS;

        $this->view->registerJs($js, \yii\web\View::POS_BEGIN);
    }
}